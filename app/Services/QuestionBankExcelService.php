<?php

namespace App\Services;

use App\Models\QuestionBankItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionBankExcelService
{
    /**
     * @return array{created:int,updated:int,errors:array<int,string>}
     */
    public function import(UploadedFile $file, int $userId, string $mode): array
    {
        $sheet = IOFactory::load($file->getRealPath())->getSheetByName('Questions');
        if (! $sheet) {
            throw new \RuntimeException('Missing "Questions" sheet.');
        }

        $rows = $sheet->toArray();
        array_shift($rows);

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $offset => $row) {
            $rowNumber = $offset + 2;
            if (trim((string) ($row[3] ?? '')) === '') {
                continue;
            }

            $payload = $this->validateRow($row, $rowNumber, $mode);
            if (is_string($payload)) {
                $errors[] = $payload;

                continue;
            }

            if ($mode === 'update_existing') {
                $item = QuestionBankItem::query()
                    ->where('id', (int) $payload['id'])
                    ->where(function ($q) use ($userId) {
                        $q->where('user_id', $userId)->orWhereNull('user_id');
                    })
                    ->first();

                if (! $item) {
                    $errors[] = "Row {$rowNumber}: Question ID not found for update.";

                    continue;
                }
                $item->update($payload['attributes']);
                $updated++;

                continue;
            }

            QuestionBankItem::create(array_merge(['user_id' => $userId], $payload['attributes']));
            $created++;
        }

        return compact('created', 'updated', 'errors');
    }

    public function exportForUser(int $userId): StreamedResponse
    {
        $items = QuestionBankItem::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->orderBy('level')
            ->orderBy('subject')
            ->orderBy('id')
            ->get();

        return $this->buildWorkbookDownload($items, 'quiz-question-bank-export.xlsx');
    }

    public function template(): StreamedResponse
    {
        return $this->buildWorkbookDownload(collect([$this->sampleRow()]), 'quiz-question-import-template.xlsx');
    }

    /**
     * @param  Collection<int,QuestionBankItem|array<string,string>>  $rows
     */
    protected function buildWorkbookDownload(Collection $rows, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Questions');

        $headers = [
            'Question ID',
            'Level',
            'Subject',
            'Question Text',
            'Option A',
            'Option B',
            'Option C',
            'Option D',
            'Correct Option',
            'Explanation',
            'Status',
        ];
        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFDE68A');

        $excelRows = [];
        foreach ($rows as $row) {
            $excelRows[] = [
                data_get($row, 'id'),
                data_get($row, 'level'),
                data_get($row, 'subject'),
                data_get($row, 'question_text'),
                data_get($row, 'option_a'),
                data_get($row, 'option_b'),
                data_get($row, 'option_c'),
                data_get($row, 'option_d'),
                data_get($row, 'correct_option'),
                data_get($row, 'explanation'),
                data_get($row, 'status'),
            ];
        }
        $sheet->fromArray($excelRows, null, 'A2');

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Pre-apply dropdown validations for user-friendly data entry.
        $this->applyDropdown($sheet, 'B2:B2000', ['Elementary', 'High School', 'Senior High School']);
        $this->applyDropdown($sheet, 'C2:C2000', ['Math', 'English', 'Science']);
        $this->applyDropdown($sheet, 'I2:I2000', ['A', 'B', 'C', 'D']);
        $this->applyDropdown($sheet, 'K2:K2000', ['Active', 'Inactive']);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename);
    }

    /**
     * @param  array<int,mixed>  $row
     * @return array{id:int|null,attributes:array<string,string>}|string
     */
    protected function validateRow(array $row, int $rowNumber, string $mode): array|string
    {
        $id = trim((string) ($row[0] ?? ''));
        $level = trim((string) ($row[1] ?? ''));
        $subject = trim((string) ($row[2] ?? ''));
        $question = trim((string) ($row[3] ?? ''));
        $a = trim((string) ($row[4] ?? ''));
        $b = trim((string) ($row[5] ?? ''));
        $c = trim((string) ($row[6] ?? ''));
        $d = trim((string) ($row[7] ?? ''));
        $correct = strtoupper(trim((string) ($row[8] ?? '')));
        $explanation = trim((string) ($row[9] ?? ''));
        $status = trim((string) ($row[10] ?? ''));

        if ($mode === 'update_existing' && $id === '') {
            return "Row {$rowNumber}: Question ID is required for Update Existing mode.";
        }

        if (! in_array($level, QuestionBankItem::LEVELS, true)) {
            return "Row {$rowNumber}: Level must be Elementary, High School, or Senior High School.";
        }
        if (! in_array($subject, QuestionBankItem::SUBJECTS, true)) {
            return "Row {$rowNumber}: Subject must be Math, English, or Science.";
        }
        if ($question === '' || $a === '' || $b === '' || $c === '' || $d === '') {
            return "Row {$rowNumber}: Question text and all four options are required.";
        }
        if (! in_array($correct, ['A', 'B', 'C', 'D'], true)) {
            return "Row {$rowNumber}: Correct Option must be A, B, C, or D.";
        }
        if (! in_array($status, QuestionBankItem::STATUSES, true)) {
            return "Row {$rowNumber}: Status must be Active or Inactive.";
        }

        return [
            'id' => $id !== '' ? (int) $id : null,
            'attributes' => [
                'level' => $level,
                'subject' => $subject,
                'question_text' => $question,
                'option_a' => $a,
                'option_b' => $b,
                'option_c' => $c,
                'option_d' => $d,
                'correct_option' => $correct,
                'explanation' => $explanation !== '' ? $explanation : null,
                'status' => $status,
            ],
        ];
    }

    /**
     * @return array<string,string>
     */
    protected function sampleRow(): array
    {
        return [
            'id' => '',
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_text' => 'What is 7 + 5?',
            'option_a' => '10',
            'option_b' => '11',
            'option_c' => '12',
            'option_d' => '13',
            'correct_option' => 'C',
            'explanation' => '7 + 5 equals 12.',
            'status' => 'Active',
        ];
    }

    /**
     * @param  list<string>  $items
     */
    protected function applyDropdown($sheet, string $range, array $items): void
    {
        foreach (explode(':', $range) as $_) {
            // noop, keep format explicit for readability
        }

        [$start, $end] = explode(':', $range);
        $startRow = (int) preg_replace('/[^0-9]/', '', $start);
        $endRow = (int) preg_replace('/[^0-9]/', '', $end);
        $column = preg_replace('/[^A-Z]/', '', $start);

        for ($row = $startRow; $row <= $endRow; $row++) {
            $validation = $sheet->getCell($column.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('"'.implode(',', $items).'"');
        }
    }
}
