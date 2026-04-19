<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\QuizImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class QuizImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_includes_first_question_on_metadata_row(): void
    {
        $user = User::factory()->create();
        $path = tempnam(sys_get_temp_dir(), 'quizimport').'.xlsx';

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            [
                'Quiz Title',
                'Description',
                'Passing Percentage',
                'Time Reward (minutes)',
                'Question',
                'Type',
                'Option A',
                'Option B',
                'Option C',
                'Option D',
                'Correct Answer',
            ],
        ], null, 'A1');
        $sheet->fromArray([
            [
                'Math Quiz',
                'Basic',
                70,
                15,
                'What is 2+1?',
                'multiple_choice',
                2,
                3,
                4,
                5,
                3,
            ],
            [
                null,
                null,
                null,
                null,
                'The capital of France is ___.',
                'fill_blank',
                null,
                null,
                null,
                null,
                'Paris',
            ],
            [
                null,
                null,
                null,
                null,
                'The sky is blue.',
                'true_false',
                'True',
                'False',
                null,
                null,
                'True',
            ],
        ], null, 'A2');

        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile($path, 'quiz.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $quiz = app(QuizImportService::class)->importFromExcel($file, $user->id);

        @unlink($path);

        $list = $quiz->questions['questions'];
        $this->assertCount(3, $list);

        $this->assertSame('What is 2+1?', $list[0]['question']);
        $this->assertSame('multiple_choice', $list[0]['type']);
        $this->assertSame([2, 3, 4, 5], array_values(array_map('intval', $list[0]['options'])));
        $this->assertSame(3, (int) $list[0]['correct_answer']);

        $this->assertSame('The capital of France is ___.', $list[1]['question']);
        $this->assertSame('The sky is blue.', $list[2]['question']);
    }

    public function test_import_preserves_zero_as_option_and_correct_answer(): void
    {
        $user = User::factory()->create();
        $path = tempnam(sys_get_temp_dir(), 'quizimport').'.xlsx';

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Quiz Title', 'Description', 'Passing Percentage', 'Time Reward (minutes)', 'Question', 'Type', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Answer'],
        ], null, 'A1');
        // Trailing numeric zeros are dropped by fromArray() when round-tripping; real Excel files keep them.
        $sheet->fromArray([
            ['Zero Quiz', null, 70, 15, '2 - 2 = ?', 'multiple_choice', 1, 2, 3],
        ], null, 'A2');
        $sheet->setCellValue('J2', 0);
        $sheet->setCellValue('K2', 0);

        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile($path, 'quiz.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $quiz = app(QuizImportService::class)->importFromExcel($file, $user->id);

        @unlink($path);

        $q = $quiz->questions['questions'][0];
        $this->assertSame(['1', '2', '3', '0'], $q['options']);
        $this->assertSame('0', $q['correct_answer']);
    }
}
