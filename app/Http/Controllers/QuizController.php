<?php

/**
 * QuizController - Parent Dashboard Quiz Management
 * 
 * This controller handles all quiz-related operations for parents in the dashboard.
 * Parents use this to create, edit, delete, and import quizzes that their children will take.
 * 
 * How it works:
 * - Parents log in and access /quizzes
 * - They can create quizzes with multiple questions
 * - Questions are stored as JSON in the database
 * - Parents can also import quizzes from Excel files
 * 
 * Security: Only authenticated parents can access these routes, and they can only
 * manage quizzes they created (checked via user_id).
 */

namespace App\Http\Controllers;

use App\Http\Requests\ImportQuizRequest;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\Quiz;
use App\Services\QuizImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuizController extends Controller
{
    /**
     * QuizImportService - Handles Excel file import functionality
     * 
     * This service is injected via dependency injection. It's used to:
     * - Read Excel files and extract quiz data
     * - Generate Excel templates for download
     * 
     * Why use a service? Separates business logic from controller, making code
     * more organized and testable.
     */
    protected QuizImportService $importService;

    /**
     * Constructor - Called automatically when controller is created
     * 
     * Laravel's dependency injection automatically provides QuizImportService.
     * This is called "dependency injection" - Laravel creates the service for us.
     */
    public function __construct(QuizImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Display a listing of quizzes for the authenticated parent.
     * 
     * Route: GET /quizzes
     * 
     * What it does:
     * 1. Gets the currently logged-in parent user
     * 2. Fetches all quizzes created by this parent
     * 3. Counts how many times each quiz has been attempted (for statistics)
     * 4. Orders them by newest first (latest())
     * 5. Displays them in a table view
     * 
     * Why withCount('attempts')? This efficiently counts quiz attempts without
     * loading all attempt records, which is faster for large datasets.
     * 
     * @return View The quizzes index page showing all quizzes in a table
     */
    public function index(): View
    {
        // Get all quizzes for the logged-in parent
        // Auth::user() gets the currently authenticated user
        // ->quizzes() gets all quizzes this parent created (via relationship)
        $quizzes = Auth::user()->quizzes()
            ->withCount('attempts')  // Count attempts for each quiz (e.g., "5 attempts")
            ->latest()                // Order by newest first (created_at DESC)
            ->get();                  // Execute query and get results

        // Return the view with quizzes data
        // compact('quizzes') creates ['quizzes' => $quizzes] array
        return view('quizzes.index', compact('quizzes'));
    }

    /**
     * Show the form for creating a new quiz.
     * 
     * Route: GET /quizzes/create
     * 
     * What it does:
     * - Displays an empty form where parents can create a new quiz
     * - Form includes fields for: title, description, passing score, time reward
     * - JavaScript dynamically adds/removes questions
     * 
     * The form is handled by JavaScript to allow adding multiple questions
     * without page reloads (dynamic form building).
     * 
     * @return View The quiz creation form
     */
    public function create(): View
    {
        // Get all devices owned by the logged-in parent so quizzes can be assigned
        $devices = Auth::user()->devices()->get();

        return view('quizzes.create', compact('devices'));
    }

    /**
     * Store a newly created quiz in storage.
     * 
     * Route: POST /quizzes
     * 
     * What it does:
     * 1. Validates the form data (via StoreQuizRequest)
     * 2. Formats questions with sequential IDs (1, 2, 3, ...)
     * 3. Stores quiz in database
     * 4. Redirects back to quiz list with success message
     * 
     * Why format questions? Questions come from form as array [0, 1, 2, ...]
     * but we want them stored with IDs [1, 2, 3, ...] for consistency.
     * 
     * Questions are stored as JSON in the database. JSON (JavaScript Object Notation)
     * is a text format for storing structured data. Example:
     * {
     *   "questions": [
     *     {"id": 1, "question": "What is 2+2?", "type": "multiple_choice", ...}
     *   ]
     * }
     * 
     * @param StoreQuizRequest $request Validated form data (title, questions, etc.)
     * @return RedirectResponse Redirects to quiz list with success message
     */
    public function store(StoreQuizRequest $request): RedirectResponse
    {
        // Get validated form data (StoreQuizRequest ensures all required fields exist)
        // This prevents invalid data from reaching the database
        $validated = $request->validated();

        // Format questions with sequential IDs
        // Questions come from form as: [0 => question1, 1 => question2, ...]
        // We convert to: [id => 1, id => 2, ...] for better readability
        $questions = [];
        foreach ($validated['questions'] as $index => $question) {
            $questions[] = [
                'id' => $index + 1,  // Start IDs at 1 (not 0) - more user-friendly
                'question' => $question['question'],  // Question text
                'type' => $question['type'],  // multiple_choice, fill_blank, or true_false
                'options' => $question['options'] ?? [],  // Options array (empty for fill_blank)
                'correct_answer' => $question['correct_answer'],  // The correct answer
            ];
        }

        // Create quiz record in database
        // Quiz::create() automatically saves to database
        $quiz = Quiz::create([
            'user_id' => Auth::id(),  // Link quiz to current parent (who created it)
            'title' => $validated['title'],  // Quiz name (e.g., "Math Quiz")
            'description' => $validated['description'] ?? null,  // Optional description
            'passing_score' => $validated['passing_score'],  // Percentage needed to pass (0-100)
            'time_reward_minutes' => $validated['time_reward_minutes'],  // Minutes granted if passed
            'questions' => ['questions' => $questions],  // Store as JSON: {questions: [...]}
            'is_active' => true,  // New quizzes are active by default
        ]);

        // Assign quiz to selected devices (if any)
        $deviceIds = $request->input('devices', []);
        $quiz->devices()->sync($deviceIds);

        // Redirect to quiz list page with success message
        // ->with() stores a message in session that displays on next page
        return redirect()->route('quizzes.index')
            ->with('success', 'Quiz created successfully!');
    }

    /**
     * Show the form for editing the specified quiz.
     * 
     * Route: GET /quizzes/{quiz}/edit
     * 
     * What it does:
     * 1. Checks if parent owns this quiz (security check)
     * 2. Loads quiz data from database
     * 3. Displays edit form pre-filled with existing quiz data
     * 
     * Security: Prevents parents from editing other parents' quizzes.
     * If someone tries to edit a quiz they don't own, they get a 403 Forbidden error.
     * 
     * @param Quiz $quiz The quiz to edit (Laravel automatically finds it by ID from URL)
     * @return View The quiz edit form with existing data
     */
    public function edit(Quiz $quiz): View
    {
        // Security check: Ensure user owns this quiz
        // Prevents unauthorized access to other parents' quizzes
        // $quiz->user_id is the parent who created it
        // Auth::id() is the currently logged-in parent
        if ($quiz->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');  // 403 = Forbidden
        }

        // Get devices owned by the parent for assignment checkboxes
        $devices = Auth::user()->devices()->get();

        // Current device assignments for this quiz (used to pre-check boxes)
        $assignedDeviceIds = $quiz->devices()->pluck('devices.id')->toArray();

        return view('quizzes.edit', compact('quiz', 'devices', 'assignedDeviceIds'));
    }

    /**
     * Update the specified quiz in storage.
     * 
     * Route: PUT /quizzes/{quiz}
     * 
     * What it does:
     * 1. Validates the updated form data
     * 2. Checks parent owns the quiz (security)
     * 3. Formats questions with IDs (same as store method)
     * 4. Updates quiz in database
     * 5. Redirects with success message
     * 
     * Special handling for is_active checkbox:
     * - HTML checkboxes don't send a value when unchecked
     * - We use a hidden input with value "0" to always get a value
     * - "0" = false, "1" = true (converted to boolean)
     * 
     * @param UpdateQuizRequest $request Validated form data
     * @param Quiz $quiz The quiz to update (found by ID from URL)
     * @return RedirectResponse Redirects to quiz list with success message
     */
    public function update(UpdateQuizRequest $request, Quiz $quiz): RedirectResponse
    {
        // Security check: Ensure user owns this quiz
        // Same check as edit() method - prevents unauthorized updates
        if ($quiz->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Get validated form data
        $validated = $request->validated();

        // Format questions with sequential IDs (same process as store method)
        $questions = [];
        foreach ($validated['questions'] as $index => $question) {
            $questions[] = [
                'id' => $index + 1,
                'question' => $question['question'],
                'type' => $question['type'],
                'options' => $question['options'] ?? [],
                'correct_answer' => $question['correct_answer'],
            ];
        }

        // Update quiz record in database
        // $quiz->update() saves changes to existing record
        $quiz->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'passing_score' => $validated['passing_score'],
            'time_reward_minutes' => $validated['time_reward_minutes'],
            'questions' => ['questions' => $questions],  // Update questions JSON
            // Convert checkbox value to boolean: "0" = false, "1" = true
            // ?? false means if is_active is missing, default to false
            'is_active' => (bool)($validated['is_active'] ?? false),
        ]);

        // Update device assignments (empty array removes all assignments)
        $deviceIds = $request->input('devices', []);
        $quiz->devices()->sync($deviceIds);

        return redirect()->route('quizzes.index')
            ->with('success', 'Quiz updated successfully!');
    }

    /**
     * Remove the specified quiz from storage.
     * 
     * Route: DELETE /quizzes/{quiz}
     * 
     * What it does:
     * 1. Checks parent owns the quiz (security)
     * 2. Checks if quiz has been attempted by children
     * 3. If attempts exist, prevents deletion (preserves history)
     * 4. If no attempts, deletes quiz from database
     * 
     * Why prevent deletion if attempts exist?
     * - Preserves quiz history for parents to review
     * - Maintains data integrity (attempts reference the quiz)
     * - Parents can deactivate instead (set is_active = false)
     * 
     * @param Quiz $quiz The quiz to delete
     * @return RedirectResponse Redirects to quiz list with message
     */
    public function destroy(Quiz $quiz): RedirectResponse
    {
        // Security check: Ensure user owns this quiz
        if ($quiz->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Safety check: Prevent deletion if quiz has attempts
        // This preserves quiz history and maintains data integrity
        // ->attempts() gets all QuizAttempt records for this quiz
        // ->count() counts how many attempts exist
        if ($quiz->attempts()->count() > 0) {
            return redirect()->route('quizzes.index')
                ->with('error', 'Cannot delete quiz with existing attempts. Please deactivate it instead.');
        }

        // Delete quiz from database
        // This permanently removes the quiz record
        $quiz->delete();

        return redirect()->route('quizzes.index')
            ->with('success', 'Quiz deleted successfully!');
    }

    /**
     * Show the Excel import form.
     * 
     * Route: GET /quizzes/import
     * 
     * What it does:
     * - Displays a form where parents can upload an Excel file
     * - Form includes file upload input and link to download template
     * 
     * Why Excel import? Allows parents to create many quizzes quickly
     * by filling out a spreadsheet instead of using the web form.
     * 
     * @return View The Excel import form
     */
    public function import(): View
    {
        return view('quizzes.import');
    }

    /**
     * Process the Excel import.
     * 
     * Route: POST /quizzes/import
     * 
     * What it does:
     * 1. Validates the uploaded Excel file (via ImportQuizRequest)
     * 2. Calls QuizImportService to read and parse the Excel file
     * 3. Creates quiz in database from Excel data
     * 4. Redirects with success or error message
     * 
     * Excel Format Expected:
     * - Row 1: Headers (Quiz Title, Description, Passing Score, Time Reward, Question, Type, Options A-D, Correct Answer)
     * - Row 2+: Quiz metadata (first row) and questions (remaining rows)
     * 
     * Error Handling: If import fails (invalid format, missing data, etc.),
     * catches the exception and shows error message to user.
     * 
     * @param ImportQuizRequest $request Validated file upload request
     * @return RedirectResponse Redirects to quiz list (success) or import form (error)
     */
    public function processImport(ImportQuizRequest $request): RedirectResponse
    {
        try {
            // Call QuizImportService to handle the Excel file
            // $request->file('excel_file') gets the uploaded file
            // Auth::id() links the quiz to the current parent
            $quiz = $this->importService->importFromExcel(
                $request->file('excel_file'),
                Auth::id()
            );

            // Success: Redirect to quiz list with success message
            return redirect()->route('quizzes.index')
                ->with('success', "Quiz '{$quiz->title}' imported successfully!");
        } catch (\Exception $e) {
            // Error: Redirect back to import form with error message
            // $e->getMessage() gets the error description
            return redirect()->route('quizzes.import')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download Excel template.
     * 
     * Route: GET /quizzes/template/download
     * 
     * What it does:
     * - Generates an Excel file with the correct format
     * - Includes headers and example rows
     * - Downloads the file to user's computer
     * 
     * Why template? Shows parents exactly how to format their Excel file
     * with headers and example data they can copy/modify.
     * 
     * @return StreamedResponse Excel file download
     */
    public function downloadTemplate()
    {
        try {
            // Call QuizImportService to generate Excel template
            // Returns a file download response
            return $this->importService->generateTemplate();
        } catch (\Exception $e) {
            // If template generation fails, redirect with error
            return redirect()->route('quizzes.import')
                ->with('error', 'Failed to generate template: ' . $e->getMessage());
        }
    }
}
