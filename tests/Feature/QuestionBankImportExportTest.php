<?php

namespace Tests\Feature;

use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Models\User;
use App\Services\QuestionBankExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function test_export_includes_ui_json_questions_when_question_bank_empty(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'UI-only quiz',
            'description' => null,
            'level' => 'Kindergarten',
            'subject' => 'Math',
            'question_count' => 5,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 15,
            'max_passes_per_day' => null,
            'retry_cooldown_minutes' => null,
            'questions' => ['questions' => [
                [
                    'id' => 1,
                    'question' => 'Two plus two equals?',
                    'type' => 'multiple_choice',
                    'options' => ['3', '4', '5', '6'],
                    'correct_answer' => '4',
                ],
            ]],
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('quizzes.question-bank.export', [
            'export_level' => 'Kindergarten',
            'quiz_ids' => [$quiz->id],
        ]));

        $response->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'qb-json').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();
        @unlink($path);

        $this->assertSame('Two plus two equals?', trim((string) $sheet->getCell('A7')->getValue()));
        $this->assertSame('3', trim((string) $sheet->getCell('C7')->getValue()));
        $this->assertSame('4', trim((string) $sheet->getCell('D7')->getValue()));
        $this->assertSame('B', trim((string) $sheet->getCell('G7')->getValue()));
        $this->assertSame('Question Text', trim((string) $sheet->getCell('A6')->getValue()));
        $this->assertSame('Question Type', trim((string) $sheet->getCell('B6')->getValue()));
        $this->assertSame('Correct Option', trim((string) $sheet->getCell('G6')->getValue()));
        $this->assertSame('', trim((string) $sheet->getCell('H6')->getValue()));
    }

    public function test_export_succeeds_when_quiz_title_contains_excel_invalid_characters(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Testing 10:05',
            'description' => null,
            'level' => 'Kindergarten',
            'subject' => 'Filipino',
            'question_count' => 5,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 15,
            'max_passes_per_day' => null,
            'retry_cooldown_minutes' => null,
            'questions' => ['questions' => [
                [
                    'id' => 1,
                    'question' => 'Sample?',
                    'type' => 'multiple_choice',
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 'B',
                ],
            ]],
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('quizzes.question-bank.export', [
            'export_level' => 'Kindergarten',
            'quiz_ids' => [$quiz->id],
        ]));

        $response->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'qb-colon').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $spreadsheet = IOFactory::load($path);
        @unlink($path);

        $this->assertStringNotContainsString(':', $spreadsheet->getActiveSheet()->getTitle());
        $this->assertSame('Sample?', trim((string) $spreadsheet->getActiveSheet()->getCell('A7')->getValue()));
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
        $quiz = Quiz::query()->where('user_id', $user->id)->where('title', 'Science — Elementary')->first();
        $this->assertNotNull($quiz);
        $this->assertDatabaseHas('question_bank_items', [
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'level' => 'Elementary',
            'subject' => 'Science',
            'question_text' => 'Sample?',
            'correct_option' => 'C',
            'question_type' => 'multiple_choice',
        ]);
        $this->assertDatabaseHas('quizzes', [
            'user_id' => $user->id,
            'title' => 'Science — Elementary',
            'level' => 'Elementary',
            'subject' => 'Science',
        ]);
    }

    public function test_modern_sheet_import_custom_subject_and_quiz_title_from_row4(): void
    {
        $user = User::factory()->create();
        $service = app(QuestionBankExcelService::class);
        $path = tempnam(sys_get_temp_dir(), 'qb-custom').'.xlsx';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import');
        $sheet->setCellValue('A4', 'School level:');
        $sheet->setCellValue('B4', 'High School');
        $sheet->setCellValue('D4', 'Subject:');
        $sheet->setCellValue('E4', 'World History');
        $sheet->setCellValue('G4', 'Quiz title:');
        $sheet->setCellValue('H4', 'Ancient Rome review');
        $sheet->fromArray([[
            'Question Text', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Option',
        ]], null, 'A5');
        $sheet->fromArray([[
            'When was Rome founded?', 'Never', '753 BCE', '1066', '1776', 'B',
        ]], null, 'A6');
        (new Xlsx($spreadsheet))->save($path);

        $result = $service->import(
            new UploadedFile($path, 'custom.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            $user->id,
            'add_new'
        );
        @unlink($path);

        $this->assertSame(1, $result['created']);
        $this->assertEmpty($result['errors']);
        $quiz = Quiz::query()->where('user_id', $user->id)->where('title', 'Ancient Rome review')->first();
        $this->assertNotNull($quiz);
        $this->assertDatabaseHas('question_bank_items', [
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'level' => 'High School',
            'subject' => 'World History',
            'question_text' => 'When was Rome founded?',
        ]);
        $this->assertDatabaseHas('quizzes', [
            'user_id' => $user->id,
            'title' => 'Ancient Rome review',
            'level' => 'High School',
            'subject' => 'World History',
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
            'quiz_id' => $quiz->id,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_text' => 'New text',
            'correct_option' => 'D',
        ]);
    }

    public function test_update_existing_clears_stale_quiz_questions_json(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Sync quiz',
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
            'questions' => ['questions' => [
                [
                    'id' => 1,
                    'question' => 'Only in JSON',
                    'type' => 'multiple_choice',
                    'options' => ['1', '2', '3', '4'],
                    'correct_answer' => '2',
                ],
            ]],
            'is_active' => true,
        ]);

        QuestionBankItem::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_text' => 'Old bank',
            'option_a' => '1',
            'option_b' => '2',
            'option_c' => '3',
            'option_d' => '4',
            'correct_option' => 'A',
            'status' => 'Active',
        ]);

        $service = app(QuestionBankExcelService::class);
        $path = tempnam(sys_get_temp_dir(), 'qb-json-clear').'.xlsx';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A4', 'School level:');
        $sheet->setCellValue('B4', 'Elementary');
        $sheet->setCellValue('D4', 'Subject:');
        $sheet->setCellValue('E4', 'Math');
        $sheet->fromArray([[
            'Question Text', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Option',
        ]], null, 'A5');
        $sheet->fromArray([[
            'Fresh from spreadsheet', '1', '2', '3', '4', 'B',
        ]], null, 'A6');
        (new Xlsx($spreadsheet))->save($path);

        $result = $service->import(
            new UploadedFile($path, 'json-clear.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            $user->id,
            'update_existing',
            $quiz->id
        );
        @unlink($path);

        $this->assertEmpty($result['errors']);
        $quiz->refresh();
        $this->assertSame(['questions' => []], $quiz->questions);
        $this->assertDatabaseHas('question_bank_items', [
            'quiz_id' => $quiz->id,
            'question_text' => 'Fresh from spreadsheet',
            'correct_option' => 'B',
        ]);
        $this->assertDatabaseMissing('question_bank_items', [
            'quiz_id' => $quiz->id,
            'question_text' => 'Old bank',
        ]);
    }

    public function test_add_new_duplicate_title_returns_pending_duplicate(): void
    {
        $user = User::factory()->create();
        Quiz::create([
            'user_id' => $user->id,
            'title' => 'Shared title',
            'description' => null,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_count' => 1,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 15,
            'max_passes_per_day' => null,
            'retry_cooldown_minutes' => null,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);

        $service = app(QuestionBankExcelService::class);
        $path = tempnam(sys_get_temp_dir(), 'qb-dup').'.xlsx';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A4', 'School level:');
        $sheet->setCellValue('B4', 'Elementary');
        $sheet->setCellValue('D4', 'Subject:');
        $sheet->setCellValue('E4', 'Math');
        $sheet->setCellValue('G4', 'Quiz title:');
        $sheet->setCellValue('H4', 'Shared title');
        $sheet->fromArray([[
            'Question Text', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Option',
        ]], null, 'A5');
        $sheet->fromArray([[
            'Q1', '1', '2', '3', '4', 'A',
        ]], null, 'A6');
        (new Xlsx($spreadsheet))->save($path);

        $result = $service->import(
            new UploadedFile($path, 'dup.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            $user->id,
            'add_new'
        );
        @unlink($path);

        $this->assertSame(0, $result['created']);
        $this->assertArrayHasKey('pending_duplicate', $result);
        $this->assertSame('Shared title', $result['pending_duplicate']['title']);
    }

    public function test_blank_question_text_row_stops_importing_further_rows(): void
    {
        $user = User::factory()->create();
        $service = app(QuestionBankExcelService::class);
        $path = tempnam(sys_get_temp_dir(), 'qb-blank').'.xlsx';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A4', 'School level:');
        $sheet->setCellValue('B4', 'Elementary');
        $sheet->setCellValue('D4', 'Subject:');
        $sheet->setCellValue('E4', 'Science');
        $sheet->fromArray([[
            'Question Text', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Option',
        ]], null, 'A5');
        $sheet->fromArray([[
            'First question', '1', '2', '3', '4', 'A',
        ]], null, 'A6');
        $sheet->fromArray([[
            '', '', '', '', '', '',
        ]], null, 'A7');
        $sheet->fromArray([[
            'After blank', '1', '2', '3', '4', 'B',
        ]], null, 'A8');
        (new Xlsx($spreadsheet))->save($path);

        $result = $service->import(
            new UploadedFile($path, 'blank-stop.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            $user->id,
            'add_new'
        );
        @unlink($path);

        $this->assertEmpty($result['errors']);
        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('question_bank_items', [
            'user_id' => $user->id,
            'question_text' => 'First question',
        ]);
        $this->assertDatabaseMissing('question_bank_items', [
            'user_id' => $user->id,
            'question_text' => 'After blank',
        ]);
    }

    public function test_modern_layout_with_question_type_row(): void
    {
        $user = User::factory()->create();
        $service = app(QuestionBankExcelService::class);
        $path = tempnam(sys_get_temp_dir(), 'qb-typed').'.xlsx';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A4', 'Quiz title:');
        $sheet->setCellValue('B4', 'Typed layout');
        $sheet->setCellValue('A5', 'School level:');
        $sheet->setCellValue('B5', 'Elementary');
        $sheet->setCellValue('D5', 'Subject:');
        $sheet->setCellValue('E5', 'Science');
        $sheet->fromArray([[
            'Question Text', 'Question Type', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Option',
        ]], null, 'A6');
        $sheet->fromArray([[
            'Is water wet?', 'True/False', 'True', 'False', '', '', 'A',
        ]], null, 'A7');
        (new Xlsx($spreadsheet))->save($path);

        $result = $service->import(
            new UploadedFile($path, 'typed.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            $user->id,
            'add_new'
        );
        @unlink($path);

        $this->assertEmpty($result['errors']);
        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('question_bank_items', [
            'user_id' => $user->id,
            'question_text' => 'Is water wet?',
            'question_type' => 'true_false',
            'correct_option' => 'A',
        ]);
    }

    public function test_http_import_duplicate_redirects_to_pending_confirmation(): void
    {
        $user = User::factory()->create();
        Quiz::create([
            'user_id' => $user->id,
            'title' => 'HTTP dup title',
            'description' => null,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_count' => 1,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 15,
            'max_passes_per_day' => null,
            'retry_cooldown_minutes' => null,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'qb-http').'.xlsx';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A4', 'School level:');
        $sheet->setCellValue('B4', 'Elementary');
        $sheet->setCellValue('D4', 'Subject:');
        $sheet->setCellValue('E4', 'Math');
        $sheet->setCellValue('G4', 'Quiz title:');
        $sheet->setCellValue('H4', 'HTTP dup title');
        $sheet->fromArray([[
            'Question Text', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Option',
        ]], null, 'A5');
        $sheet->fromArray([[
            'Q?', '1', '2', '3', '4', 'B',
        ]], null, 'A6');
        (new Xlsx($spreadsheet))->save($path);

        $response = $this->actingAs($user)->post(route('quizzes.import.process'), [
            'mode' => 'add_new',
            'excel_file' => new UploadedFile($path, 'dup-http.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ]);
        @unlink($path);

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('/quizzes/import/pending/', $location);
        if (preg_match('#/import/pending/([^/?]+)#', $location, $m)) {
            $token = $m[1];
            $this->actingAs($user)->get(route('quizzes.import.pending', ['token' => $token]))
                ->assertOk()
                ->assertSee('HTTP dup title', false);
        }
    }
}
