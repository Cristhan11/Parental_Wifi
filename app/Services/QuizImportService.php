<?php

/**
 * QuizImportService - Excel Import/Export for Quizzes
 * 
 * This service handles importing quizzes from Excel files and generating
 * Excel templates for download. It uses PhpSpreadsheet library to read/write Excel files.
 * 
 * Why a separate service? Separates file processing logic from controller,
 * making code more organized, testable, and reusable.
 * 
 * Excel Format Expected:
 * Row 1: Headers (Quiz Title, Description, Passing Score, Time Reward, Question, Type, Option A-D, Correct Answer)
 * Row 2: Quiz metadata (first row) + First question
 * Row 3+: Additional questions (quiz metadata columns left empty)
 * 
 * Example:
 * | Quiz Title | Description | Passing Score | Time Reward | Question | Type | Option A | Option B | Option C | Option D | Correct Answer |
 * | Math Quiz  | Basic math  | 70           | 15          | What is 2+2? | multiple_choice | 2 | 3 | 4 | 5 | 4 |
 * |            |             |              |             | Capital of France? | fill_blank |   |   |   |   | Paris |
 */

namespace App\Services;

use App\Models\Quiz;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuizImportService
{
    /**
     * Import quiz from Excel file.
     * 
     * This method reads an Excel file, parses quiz data, and creates a quiz
     * in the database. It handles different question types and normalizes
     * data formats.
     * 
     * Process:
     * 1. Load Excel file using PhpSpreadsheet library
     * 2. Convert worksheet to array (rows and columns)
     * 3. Parse first row → Quiz metadata (title, description, etc.)
     * 4. Parse remaining rows → Questions
     * 5. Normalize question types (handle variations like "Multiple Choice" → "multiple_choice")
     * 6. Create quiz in database
     * 
     * Error Handling: Wraps in try-catch to handle file reading errors,
     * invalid formats, or missing data. Logs errors for debugging.
     * 
     * @param UploadedFile $file Excel file uploaded by parent
     * @param int $userId Parent user ID (who will own the imported quiz)
     * @return Quiz The created quiz instance
     * @throws \Exception If file is invalid, empty, or parsing fails
     */
    public function importFromExcel(UploadedFile $file, int $userId): Quiz
    {
        try {
            // Step 1: Load Excel file
            // IOFactory::load() reads the Excel file from temporary upload location
            // getRealPath() gets the actual file path on server
            $spreadsheet = IOFactory::load($file->getRealPath());
            
            // Get the active worksheet (first sheet in Excel file)
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Convert worksheet to array: [[row1], [row2], ...]
            // Each row is an array: [col1, col2, col3, ...]
            $rows = $worksheet->toArray();

            // Step 2: Skip header row (first row contains column names)
            // array_shift() removes first element from array
            array_shift($rows);

            // Validation: Check if file has data rows
            if (empty($rows)) {
                throw new \Exception('Excel file is empty or has no data rows.');
            }

            // Step 3: Parse quiz metadata from first data row
            // Excel columns: A=0 (Title), B=1 (Description), C=2 (Passing Score), D=3 (Time Reward)
            $firstRow = $rows[0];
            $quizTitle = $firstRow[0] ?? 'Imported Quiz';        // Column A: Quiz title (default if empty)
            $description = $firstRow[1] ?? null;                 // Column B: Description (optional)
            $passingScore = (int)($firstRow[2] ?? 70);           // Column C: Passing score % (default 70)
            $timeReward = (int)($firstRow[3] ?? 15);             // Column D: Time reward minutes (default 15)
            // (int) converts string to integer (e.g., "70" → 70)

            // Step 4: Parse questions from remaining rows
            // Excel columns: E=4 (Question), F=5 (Type), G-J=6-9 (Options A-D), K=10 (Correct Answer)
            $questions = [];
            $questionRows = array_slice($rows, 1);  // Skip first row (quiz metadata), get remaining rows

            foreach ($questionRows as $index => $row) {
                // Skip rows where question text is empty (allows blank rows in Excel)
                if (empty($row[4])) {
                    continue;
                }

                // Build question array
                $question = [
                    'id' => $index + 1,  // Sequential ID starting at 1
                    'question' => $row[4] ?? '',  // Column E: Question text
                    // Normalize type: "Multiple Choice" → "multiple_choice", "MC" → "multiple_choice", etc.
                    'type' => $this->normalizeType($row[5] ?? 'multiple_choice'),
                    'correct_answer' => $row[10] ?? '',  // Column K: Correct answer
                ];

                // Add options only for multiple_choice and true_false
                // Fill-in-the-blank questions don't need options
                if (in_array($question['type'], ['multiple_choice', 'true_false'])) {
                    // Get options from columns G-J (Option A, B, C, D)
                    // array_filter() removes empty options (allows fewer than 4 options)
                    $question['options'] = array_filter([
                        $row[6] ?? '',  // Column G: Option A
                        $row[7] ?? '',  // Column H: Option B
                        $row[8] ?? '',  // Column I: Option C
                        $row[9] ?? '',  // Column J: Option D
                    ]);
                }

                $questions[] = $question;
            }

            if (empty($questions)) {
                throw new \Exception('No valid questions found in Excel file.');
            }

            // Create quiz in database
            $quiz = Quiz::create([
                'user_id' => $userId,
                'title' => $quizTitle,
                'description' => $description,
                'passing_score' => $passingScore,
                'time_reward_minutes' => $timeReward,
                'questions' => ['questions' => $questions], // Store as JSON
                'is_active' => true,
            ]);

            return $quiz;
        } catch (\Exception $e) {
            Log::error('Quiz import failed: ' . $e->getMessage());
            throw new \Exception('Failed to import quiz: ' . $e->getMessage());
        }
    }

    /**
     * Normalize question type from Excel input.
     * 
     * Parents might type question types in various formats:
     * - "Multiple Choice", "multiple choice", "MC", "multiple-choice"
     * - "Fill in the Blank", "fill blank", "fill-in-the-blank", "fill"
     * - "True False", "true/false", "TF"
     * 
     * This method converts all variations to standard format:
     * - "multiple_choice"
     * - "fill_blank"
     * - "true_false"
     * 
     * Why normalize? Ensures consistent data format in database,
     * making it easier to process questions later.
     * 
     * @param string $type Question type from Excel (may be in various formats)
     * @return string Normalized question type (multiple_choice, fill_blank, or true_false)
     */
    protected function normalizeType(string $type): string
    {
        // Convert to lowercase and remove extra spaces
        // Example: "Multiple Choice" → "multiple choice"
        $type = strtolower(trim($type));
        
        // Map common variations to standard types
        // This handles different ways parents might type question types
        $mapping = [
            // Multiple Choice variations
            'multiple choice' => 'multiple_choice',
            'multiple-choice' => 'multiple_choice',
            'mc' => 'multiple_choice',
            
            // Fill-in-the-Blank variations
            'fill blank' => 'fill_blank',
            'fill-in-the-blank' => 'fill_blank',
            'fill in the blank' => 'fill_blank',
            'fill' => 'fill_blank',
            
            // True/False variations
            'true false' => 'true_false',
            'true/false' => 'true_false',
            'tf' => 'true_false',
        ];

        // Return mapped type if found, otherwise return original
        // ?? is null coalescing operator: if mapping[$type] doesn't exist, use $type
        return $mapping[$type] ?? $type;
    }

    /**
     * Generate and download Excel template.
     * 
     * Creates an Excel file with the correct format for quiz import.
     * Parents can download this template, fill it in, and upload it.
     * 
     * Template includes:
     * - Header row with column names
     * - Example rows showing each question type:
     *   1. Multiple Choice example
     *   2. Fill-in-the-Blank example
     *   3. True/False example
     * - Styling: Bold headers, yellow background
     * - Auto-sized columns for readability
     * 
     * Why template? Shows parents exactly how to format their Excel file,
     * reducing errors and making import process smoother.
     * 
     * @return StreamedResponse Excel file download response
     */
    public function generateTemplate(): StreamedResponse
    {
        // Create new Excel spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Step 1: Set header row (column names)
        // These are the column labels parents will see in Excel
        $headers = [
            'Quiz Title',           // Column A: Quiz name
            'Description',          // Column B: Optional description
            'Passing Score (%)',    // Column C: Percentage needed to pass (0-100)
            'Time Reward (minutes)', // Column D: Minutes granted if passed
            'Question',             // Column E: Question text
            'Type',                 // Column F: multiple_choice, fill_blank, or true_false
            'Option A',             // Column G: First option (for multiple choice)
            'Option B',             // Column H: Second option
            'Option C',             // Column I: Third option
            'Option D',             // Column J: Fourth option
            'Correct Answer',       // Column K: The correct answer
        ];

        // Write headers to row 1, starting at column A
        $sheet->fromArray([$headers], null, 'A1');

        // Step 2: Add example rows showing correct format
        // These examples help parents understand how to fill out the template
        $examples = [
            [
                'Math Quiz',
                'Basic math questions',
                '70',
                '15',
                'What is 2+2?',
                'multiple_choice',
                '2',
                '3',
                '4',
                '5',
                '4',
            ],
            [
                '',
                '',
                '',
                '',
                'The capital of France is ___.',
                'fill_blank',
                '',
                '',
                '',
                '',
                'Paris',
            ],
            [
                '',
                '',
                '',
                '',
                'The sky is blue.',
                'true_false',
                'True',
                'False',
                '',
                '',
                'True',
            ],
        ];

        // Write example rows starting at row 2 (row 1 is headers)
        $sheet->fromArray($examples, null, 'A2');

        // Step 3: Style the header row for better visibility
        // Make headers bold and yellow background (matches app color scheme)
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);  // Bold text
        $sheet->getStyle('A1:K1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFF00');  // Yellow background (ARGB format)

        // Step 4: Auto-size columns so all content is visible
        // This makes the template easier to read without manual column resizing
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Step 5: Create Excel writer and prepare download
        $writer = new Xlsx($spreadsheet);  // Xlsx format (Excel 2007+)
        $filename = 'quiz_import_template.xlsx';

        // Return file download response
        // streamDownload() sends file to browser as download
        // php://output sends file directly to browser (no temp file needed)
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename);
    }
}

