<?php

namespace Tests\Feature;

use App\Models\QuestionBankItem;
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

    public function test_export_and_reimport_update_existing_round_trip(): void
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
}
