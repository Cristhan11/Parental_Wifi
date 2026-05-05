<?php

namespace Tests\Feature;

use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Models\User;
use App\Services\QuestionBankExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class QuestionBankImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_rejects_invalid_level_with_plain_language_row_error(): void
    {
        $user = User::factory()->create();
        $path = tempnam(sys_get_temp_dir(), 'qb').'.xlsx';

        $sheet = (new Spreadsheet)->getActiveSheet();
        $sheet->setTitle('Questions');
        $sheet->fromArray([[
            'Question ID', 'Level', 'Subject', 'Question Text', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Option', 'Explanation', 'Status',
        ]], null, 'A1');
        $sheet->fromArray([[
            '', 'College', 'Math', 'Q?', '1', '2', '3', '4', 'A', '', 'Active',
        ]], null, 'A2');
        (new Xlsx($sheet->getParent()))->save($path);

        $response = $this->actingAs($user)->post(route('quizzes.import.process'), [
            'mode' => 'add_new',
            'excel_file' => new UploadedFile($path, 'invalid.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ]);

        @unlink($path);
        $response->assertRedirect(route('quizzes.import'));
        $this->assertStringContainsString('Row 2', session('error', ''));
    }

    public function test_export_requires_level_and_quizzes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('quizzes.question-bank.export'))
            ->assertRedirect(route('quizzes.import'))
            ->assertSessionHasErrors(['export_level', 'quiz_ids']);
    }

    public function test_export_streams_xlsx_for_quiz_scope(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Scope Quiz',
            'description' => null,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_count' => 10,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 15,
            'max_passes_per_day' => null,
            'retry_cooldown_minutes' => null,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);

        QuestionBankItem::create([
            'user_id' => $user->id,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_text' => 'Export me',
            'option_a' => '1',
            'option_b' => '2',
            'option_c' => '3',
            'option_d' => '4',
            'correct_option' => 'A',
            'status' => 'Active',
        ]);

        QuestionBankItem::create([
            'user_id' => $user->id,
            'level' => 'High School',
            'subject' => 'Math',
            'question_text' => 'Wrong level',
            'option_a' => '1',
            'option_b' => '2',
            'option_c' => '3',
            'option_d' => '4',
            'correct_option' => 'B',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->get(route('quizzes.question-bank.export', [
            'export_level' => 'Elementary',
            'quiz_ids' => [$quiz->id],
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', (string) $response->headers->get('content-type'));
    }

    public function test_export_and_reimport_update_existing_round_trip_legacy_sheet(): void
    {
        $user = User::factory()->create();
        $item = QuestionBankItem::create([
            'user_id' => $user->id,
            'level' => 'Elementary',
            'subject' => 'English',
            'question_text' => 'Original question',
            'option_a' => 'A1',
            'option_b' => 'B1',
            'option_c' => 'C1',
            'option_d' => 'D1',
            'correct_option' => 'A',
            'status' => 'Active',
        ]);

        $service = app(QuestionBankExcelService::class);
        $path = tempnam(sys_get_temp_dir(), 'qb-round').'.xlsx';
        $sheet = (new Spreadsheet)->getActiveSheet();
        $sheet->setTitle('Questions');
        $sheet->fromArray([[
            'Question ID', 'Level', 'Subject', 'Question Text', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Option', 'Explanation', 'Status',
        ]], null, 'A1');
        $sheet->fromArray([[
            (string) $item->id, 'Elementary', 'English', 'Updated question', 'A2', 'B2', 'C2', 'D2', 'B', 'Updated explanation', 'Active',
        ]], null, 'A2');
        (new Xlsx($sheet->getParent()))->save($path);

        $result = $service->import(
            new UploadedFile($path, 'roundtrip.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            $user->id,
            'update_existing'
        );
        @unlink($path);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertEmpty($result['errors']);
        $this->assertDatabaseHas('question_bank_items', [
            'id' => $item->id,
            'question_text' => 'Updated question',
            'correct_option' => 'B',
        ]);
    }

    public function test_modern_sheet_import_add_new(): void
    {
        $user = User::factory()->create();
        $service = app(QuestionBankExcelService::class);
        $path = tempnam(sys_get_temp_dir(), 'qb-mod').'.xlsx';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Science');
        $sheet->setCellValue('A1', 'Instructions');
        $sheet->setCellValue('A4', 'School level:');
        $sheet->setCellValue('B4', 'Elementary');
        $sheet->setCellValue('D4', 'Subject:');
        $sheet->setCellValue('E4', 'Science');
        $sheet->fromArray([[
            'Question Text', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Option',
        ]], null, 'A5');
        $sheet->fromArray([[
            'Sample?', '1', '2', '3', '4', 'C',
        ]], null, 'A6');
        (new Xlsx($spreadsheet))->save($path);

        $result = $service->import(
            new UploadedFile($path, 'modern.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            $user->id,
            'add_new'
        );
        @unlink($path);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertEmpty($result['errors']);
        $this->assertDatabaseHas('question_bank_items', [
            'user_id' => $user->id,
            'level' => 'Elementary',
            'subject' => 'Science',
            'question_text' => 'Sample?',
            'correct_option' => 'C',
        ]);
    }

    public function test_modern_sheet_update_with_bank_id_column(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Math — Elementary',
            'description' => null,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_count' => 10,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 15,
            'max_passes_per_day' => null,
            'retry_cooldown_minutes' => null,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);
        $item = QuestionBankItem::create([
            'user_id' => $user->id,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_text' => 'Old',
            'option_a' => '1',
            'option_b' => '2',
            'option_c' => '3',
            'option_d' => '4',
            'correct_option' => 'A',
            'status' => 'Active',
        ]);

        $service = app(QuestionBankExcelService::class);
        $path = tempnam(sys_get_temp_dir(), 'qb-bank').'.xlsx';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Math');
        $sheet->setCellValue('A4', 'School level:');
        $sheet->setCellValue('B4', 'Elementary');
        $sheet->setCellValue('D4', 'Subject:');
        $sheet->setCellValue('E4', 'Math');
        $sheet->fromArray([[
            'Question Text', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Option', 'Bank ID',
        ]], null, 'A5');
        $sheet->fromArray([[
            'New text', '1', '2', '3', '4', 'D', (string) $item->id,
        ]], null, 'A6');
        (new Xlsx($spreadsheet))->save($path);

        $result = $service->import(
            new UploadedFile($path, 'bank.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            $user->id,
            'update_existing',
            $quiz->id
        );
        @unlink($path);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertEmpty($result['errors']);
        $this->assertDatabaseMissing('question_bank_items', [
            'id' => $item->id,
        ]);
        $this->assertDatabaseHas('question_bank_items', [
            'user_id' => $user->id,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_text' => 'New text',
            'correct_option' => 'D',
        ]);
    }
}
