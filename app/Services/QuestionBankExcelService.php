<?php

namespace App\Services;

use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Support\QuizSchoolLevel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionBankExcelService
{
    private const META_LEVEL_LABEL_COL = 'A';

    private const META_LEVEL_VALUE_COL = 'B';

    private const META_SUBJECT_LABEL_COL = 'D';

    private const META_SUBJECT_VALUE_COL = 'E';

    private const META_ROW = 4;

    private const HEADER_ROW = 5;

    private const DATA_START_ROW = 6;

    /** @return array{created:int,updated:int,errors:array<int,string>} */
    public function import(UploadedFile $file, int $userId, string $mode, ?int $quizId = null): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());

        $legacySheet = $spreadsheet->getSheetByName('Questions');
        if ($legacySheet && trim((string) $legacySheet->getCell('A1')->getValue()) === 'Question ID') {
            return $this->importLegacySheet($legacySheet, $userId, $mode);
        }

        return $this->importModernWorkbook($spreadsheet, $userId, $mode, $quizId);
    }

    /**
     * @return array{created:int,updated:int,errors:array<int,string>}
     */
    protected function importLegacySheet(Worksheet $sheet, int $userId, string $mode): array
    {
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

            $payload = $this->validateLegacyRow($row, $rowNumber, $mode);
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

    /**
     * @return array{created:int,updated:int,errors:array<int,string>}
     */
    protected function importModernWorkbook(Spreadsheet $spreadsheet, int $userId, string $mode, ?int $quizId): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        $targetQuiz = null;
        $targetLevel = null;
        $targetSubject = null;
        if ($mode === 'update_existing') {
            if (! $quizId) {
                return [
                    'created' => 0,
                    'updated' => 0,
                    'errors' => ['Please select a quiz to update.'],
                ];
            }

            $targetQuiz = Quiz::query()
                ->where('id', $quizId)
                ->where('user_id', $userId)
                ->first();

            if (! $targetQuiz) {
                return [
                    'created' => 0,
                    'updated' => 0,
                    'errors' => ['Selected quiz was not found.'],
                ];
            }

            $targetLevel = (string) ($targetQuiz->level ?? '');
            $targetSubject = (string) ($targetQuiz->subject ?? '');
            if ($targetLevel === '' || $targetSubject === '') {
                return [
                    'created' => 0,
                    'updated' => 0,
                    'errors' => ['Selected quiz must have a School level and Subject to update its bank.'],
                ];
            }
        }

        /** @var list<array<string, string|null>> $replaceRows */
        $replaceRows = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if ($sheet->getSheetState() === Worksheet::SHEETSTATE_HIDDEN) {
                continue;
            }

            $sheetLabel = $sheet->getTitle();
            $parsed = $this->parseModernSheet($sheet);
            if ($parsed === null) {
                continue;
            }

            [$level, $subject, $headerRow, $bankIdColumnIndex] = $parsed;

            if ($mode === 'update_existing') {
                if ($level !== $targetLevel || $subject !== $targetSubject) {
                    $errors[] = "Sheet \"{$sheetLabel}\": School level and Subject must match the selected quiz (expected {$targetSubject} — {$targetLevel}).";
                    continue;
                }
            }

            if (! in_array($level, QuizSchoolLevel::levels(), true)) {
                $errors[] = "Sheet \"{$sheetLabel}\": School level must be one of: ".implode(', ', QuizSchoolLevel::levels()).'.';

                continue;
            }
            if (! in_array($subject, QuestionBankItem::SUBJECTS, true)) {
                $errors[] = "Sheet \"{$sheetLabel}\": Subject must be Math, English, or Science.";

                continue;
            }

            $rows = $sheet->toArray();
            $dataRows = array_slice($rows, $headerRow + 1);

            if ($mode === 'add_new') {
                $this->getOrCreateQuizForBank($userId, $level, $subject);
            }

            foreach ($dataRows as $offset => $row) {
                $rowNumber = $headerRow + $offset + 2;
                if (trim((string) ($row[0] ?? '')) === '') {
                    continue;
                }

                $payload = $this->validateModernRow(
                    $row,
                    $rowNumber,
                    $level,
                    $subject,
                    $mode,
                    $bankIdColumnIndex,
                    "Sheet \"{$sheetLabel}\""
                );
                if (is_string($payload)) {
                    $errors[] = $payload;

                    continue;
                }

                if ($mode === 'update_existing') {
                    $replaceRows[] = $payload['attributes'];
                    continue;
                }

                QuestionBankItem::create(array_merge(['user_id' => $userId], $payload['attributes']));
                $created++;
            }
        }

        if ($mode === 'update_existing' && $errors === []) {
            $targetLevel = (string) $targetLevel;
            $targetSubject = (string) $targetSubject;
            if ($replaceRows === []) {
                $errors[] = 'No question rows found for the selected quiz.';
            } else {
                DB::transaction(function () use ($userId, $targetLevel, $targetSubject, $replaceRows, &$created, &$updated): void {
                    $deleted = QuestionBankItem::query()
                        ->where('user_id', $userId)
                        ->where('level', $targetLevel)
                        ->where('subject', $targetSubject)
                        ->delete();

                    $updated = (int) $deleted;
                    $created = 0;

                    foreach ($replaceRows as $attrs) {
                        QuestionBankItem::create(array_merge(['user_id' => $userId], $attrs));
                        $created++;
                    }
                });
            }
        }

        if ($created === 0 && $updated === 0 && $errors === []) {
            $errors[] = 'No question rows found. Use the downloaded template layout (metadata row, header row, then data), or the legacy "Questions" sheet with Question ID in column A.';
        }

        return compact('created', 'updated', 'errors');
    }

    protected function getOrCreateQuizForBank(int $userId, string $level, string $subject): Quiz
    {
        $existing = Quiz::query()
            ->where('user_id', $userId)
            ->where('title', '!=', Quiz::RANDOM_MODE_SETTINGS_TITLE)
            ->where('level', $level)
            ->where('subject', $subject)
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $title = trim($subject.' — '.$level);

        return Quiz::create([
            'user_id' => $userId,
            'title' => $title !== '' ? $title : 'Imported quiz',
            'description' => null,
            'level' => $level,
            'subject' => $subject,
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
    }

    /**
     * @return array{0:string,1:string,2:int,3:?int}|null [level, subject, headerRowIndex0Based, bankIdColumnIndex]
     */
    protected function parseModernSheet(Worksheet $sheet): ?array
    {
        $rows = $sheet->toArray();
        $headerRowIndex = null;
        $bankIdColumnIndex = null;

        foreach ($rows as $idx => $row) {
            $a = trim((string) ($row[0] ?? ''));
            if ($a === 'Question Text') {
                $headerRowIndex = $idx;
                foreach ($row as $columnIndex => $cellValue) {
                    if (trim((string) $cellValue) === 'Bank ID') {
                        $bankIdColumnIndex = (int) $columnIndex;
                        break;
                    }
                }

                break;
            }
        }

        if ($headerRowIndex === null) {
            return null;
        }

        $metaRowIdx = self::META_ROW - 1;
        $meta = $rows[$metaRowIdx] ?? [];

        $level = trim((string) ($meta[1] ?? ''));
        $subject = trim((string) ($meta[4] ?? ''));

        if ($subject === '' || $level === '') {
            $title = $sheet->getTitle();
            if (str_contains($title, ' — ')) {
                [$parsedSubject, $parsedLevel] = array_map('trim', explode(' — ', $title, 2));
                if ($subject === '' && in_array($parsedSubject, QuestionBankItem::SUBJECTS, true)) {
                    $subject = $parsedSubject;
                }
                if ($level === '' && in_array($parsedLevel, QuizSchoolLevel::levels(), true)) {
                    $level = $parsedLevel;
                }
            } elseif (in_array($title, QuestionBankItem::SUBJECTS, true)) {
                if ($subject === '') {
                    $subject = $title;
                }
            }
        }

        if ($level === '' || $subject === '') {
            return null;
        }

        return [$level, $subject, $headerRowIndex, $bankIdColumnIndex];
    }

    public function exportForQuiz(Quiz $quiz, array $subjects, int $userId): StreamedResponse
    {
        $subjects = array_values(array_intersect($subjects, QuestionBankItem::SUBJECTS));
        if ($subjects === []) {
            throw new \InvalidArgumentException('Select at least one subject to export.');
        }

        $query = QuestionBankItem::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->whereIn('subject', $subjects)
            ->orderBy('id');

        if ($quiz->isRandomModeSettingsQuiz() || ! $quiz->level) {
            $query->whereIn('level', QuizSchoolLevel::levels());
        } else {
            $query->where('level', $quiz->level);
        }

        $items = $query->get();

        $sheetsData = [];
        if ($quiz->isRandomModeSettingsQuiz() || ! $quiz->level) {
            $grouped = $items->groupBy(fn (QuestionBankItem $i): string => $i->subject."\x1E".$i->level);
            foreach ($grouped as $key => $group) {
                [$sub, $lvl] = explode("\x1E", $key, 2);
                if (! in_array($sub, $subjects, true) || $group->isEmpty()) {
                    continue;
                }
                $tabTitle = $this->excelSheetTitle($sub.' — '.$lvl);
                $sheetsData[$tabTitle] = [$group, $lvl, $sub];
            }
            ksort($sheetsData);
        } else {
            $bySubject = $items->groupBy('subject')->sortKeys();
            foreach ($subjects as $sub) {
                $group = $bySubject->get($sub, collect());
                if ($group->isEmpty()) {
                    continue;
                }
                $sheetsData[$this->excelSheetTitle($sub)] = [$group, (string) $quiz->level, $sub];
            }
        }

        if ($sheetsData === []) {
            throw new \InvalidArgumentException('No question bank rows match this quiz and subject selection.');
        }

        $safeTitle = preg_replace('/[^\p{L}\p{N}\s_-]+/u', '', $quiz->title) ?? 'quiz';
        $safeTitle = substr(trim(str_replace(['/', '\\', '*', '[', ']', ':', '?'], '_', $safeTitle)), 0, 40);
        $filename = 'question-bank-'.($safeTitle !== '' ? $safeTitle : 'export').'.xlsx';

        return $this->buildMultiSubjectWorkbookDownload($sheetsData, $filename, includeBankIds: true);
    }

    public function template(): StreamedResponse
    {
        $sample = $this->sampleRowArray();
        $sheetsData = [
            'Question bank' => [collect([$sample]), 'Elementary', 'Math'],
        ];

        return $this->buildMultiSubjectWorkbookDownload($sheetsData, 'quiz-question-import-template.xlsx', includeBankIds: false);
    }

    /**
     * @param  array<string, array{0: Collection<int, QuestionBankItem|array<string, mixed>>, 1: string, 2: string}>  $sheetsData  subject key => [rows, levelLabel, subjectLabel]
     */
    protected function buildMultiSubjectWorkbookDownload(array $sheetsData, string $filename, bool $includeBankIds): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $first = true;

        foreach ($sheetsData as $subjectKey => [$rows, $levelLabel, $subjectLabel]) {
            if ($first) {
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle($this->excelSheetTitle($subjectKey));
                $first = false;
            } else {
                $sheet = new Worksheet($spreadsheet, $this->excelSheetTitle($subjectKey));
                $spreadsheet->addSheet($sheet);
            }

            $this->fillSubjectSheet($sheet, $rows, $levelLabel, $subjectLabel, $includeBankIds);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  Collection<int, QuestionBankItem|array<string, mixed>>  $rows
     */
    protected function fillSubjectSheet(Worksheet $sheet, Collection $rows, string $levelLabel, string $subjectLabel, bool $includeBankIds): void
    {
        $lastColLetter = $includeBankIds ? 'G' : 'F';
        $title = $this->instructionSheetTitle($subjectLabel);
        $body = $this->instructionBodyText($includeBankIds);

        $sheet->mergeCells("A1:{$lastColLetter}1");
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $sheet->mergeCells("A2:{$lastColLetter}3");
        $sheet->setCellValue('A2', $body);
        $sheet->getStyle('A2')->getFont()->setSize(10);
        $sheet->getStyle('A2')->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);
        $sheet->getRowDimension(2)->setRowHeight(72);
        $sheet->getRowDimension(3)->setRowHeight(72);

        $sheet->setCellValue(self::META_LEVEL_LABEL_COL.self::META_ROW, 'School level:');
        $sheet->setCellValue(self::META_LEVEL_VALUE_COL.self::META_ROW, $levelLabel);
        $sheet->setCellValue(self::META_SUBJECT_LABEL_COL.self::META_ROW, 'Subject:');
        $sheet->setCellValue(self::META_SUBJECT_VALUE_COL.self::META_ROW, $subjectLabel);
        $sheet->getStyle('A'.self::META_ROW.':E'.self::META_ROW)->getFont()->setBold(true);
        $sheet->getStyle('A'.self::META_ROW.':E'.self::META_ROW)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');

        // Allow editing with dropdowns for consistent values
        $this->applyDropdown($sheet, self::META_LEVEL_VALUE_COL.self::META_ROW.':'.self::META_LEVEL_VALUE_COL.self::META_ROW, QuizSchoolLevel::levels());
        $this->applyDropdown($sheet, self::META_SUBJECT_VALUE_COL.self::META_ROW.':'.self::META_SUBJECT_VALUE_COL.self::META_ROW, QuestionBankItem::SUBJECTS);

        $headers = [
            'Question Text',
            'Option A',
            'Option B',
            'Option C',
            'Option D',
            'Correct Option',
        ];
        if ($includeBankIds) {
            $headers[] = 'Bank ID';
        }

        $sheet->fromArray([$headers], null, 'A'.self::HEADER_ROW);
        $sheet->getStyle('A'.self::HEADER_ROW.':'.$lastColLetter.self::HEADER_ROW)->getFont()->setBold(true);
        $sheet->getStyle('A'.self::HEADER_ROW.':'.$lastColLetter.self::HEADER_ROW)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFDE68A');

        $excelRows = [];
        foreach ($rows as $row) {
            $r = [
                data_get($row, 'question_text'),
                data_get($row, 'option_a'),
                data_get($row, 'option_b'),
                data_get($row, 'option_c'),
                data_get($row, 'option_d'),
                data_get($row, 'correct_option'),
            ];
            if ($includeBankIds) {
                $r[] = data_get($row, 'id');
            }
            $excelRows[] = $r;
        }
        $sheet->fromArray($excelRows, null, 'A'.self::DATA_START_ROW);

        foreach (range('A', $lastColLetter) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastDataRow = self::DATA_START_ROW + max(count($excelRows), 1) + 500;
        $this->applyDropdown($sheet, 'F'.self::DATA_START_ROW.':F'.$lastDataRow, ['A', 'B', 'C', 'D']);

        if ($includeBankIds) {
            $sheet->getColumnDimension('G')->setWidth(12);
            $sheet->getStyle('G'.self::HEADER_ROW.':G'.$lastDataRow)->getFont()->getColor()->setARGB('FF6B7280');
        }
    }

    protected function instructionSheetTitle(string $subjectLabel): string
    {
        return 'Question bank — '.$subjectLabel;
    }

    protected function instructionBodyText(bool $includeBankIds): string
    {
        $idLine = $includeBankIds
            ? '3. Bank ID: leave blank for new rows. Exported files include this column; use Import mode "Update Existing" only when IDs are present.'
            : '3. New rows do not need an ID — the app assigns Bank IDs when you import.';

        return "How to add questions:\n"
            .'1. Edit row '.self::META_ROW.' (School level / Subject) to match what you are importing. Imports apply those values to every question on this sheet.'."\n"
            .'2. Starting at row '.self::DATA_START_ROW.', add one question per row: Question Text, all four options (A–D), and Correct option (letter A–D).'."\n"
            .$idLine."\n"
            .'4. Use the dropdown in column F for Correct option.';
    }

    protected function excelSheetTitle(string $subject): string
    {
        $t = substr($subject, 0, 31);

        return $t !== '' ? $t : 'Sheet';
    }

    /**
     * @param  array<int,mixed>  $row
     * @return array{id:int|null,attributes:array<string,string>}|string
     */
    protected function validateLegacyRow(array $row, int $rowNumber, string $mode): array|string
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

        if ($mode === 'update_existing' && $id === '') {
            return "Row {$rowNumber}: Question ID is required for Update Existing mode.";
        }

        return $this->validateCoreFields(
            $rowNumber,
            $level,
            $subject,
            $question,
            $a,
            $b,
            $c,
            $d,
            $correct,
            $id !== '' ? (int) $id : null,
            ''
        );
    }

    /**
     * @param  array<int,mixed>  $row
     * @return array{id:int|null,attributes:array<string,string>,context:string}|string
     */
    protected function validateModernRow(
        array $row,
        int $rowNumber,
        string $level,
        string $subject,
        string $mode,
        ?int $bankIdColumnIndex,
        string $context
    ): array|string {
        $question = trim((string) ($row[0] ?? ''));
        $a = trim((string) ($row[1] ?? ''));
        $b = trim((string) ($row[2] ?? ''));
        $c = trim((string) ($row[3] ?? ''));
        $d = trim((string) ($row[4] ?? ''));
        $correct = strtoupper(trim((string) ($row[5] ?? '')));
        $idRaw = $bankIdColumnIndex !== null ? trim((string) ($row[$bankIdColumnIndex] ?? '')) : '';
        $id = $idRaw !== '' ? (int) $idRaw : null;

        $result = $this->validateCoreFields(
            $rowNumber,
            $level,
            $subject,
            $question,
            $a,
            $b,
            $c,
            $d,
            $correct,
            $id,
            $context
        );

        if (is_string($result)) {
            return $result;
        }

        return array_merge($result, ['context' => $context]);
    }

    /**
     * @return array{id:int|null,attributes:array<string,string>}|string
     */
    protected function validateCoreFields(
        int $rowNumber,
        string $level,
        string $subject,
        string $question,
        string $a,
        string $b,
        string $c,
        string $d,
        string $correct,
        ?int $id,
        string $contextPrefix
    ): array|string {
        $prefix = $contextPrefix !== '' ? $contextPrefix.' ' : '';

        if (! in_array($level, QuizSchoolLevel::levels(), true)) {
            return "{$prefix}Row {$rowNumber}: Level must be one of: ".implode(', ', QuizSchoolLevel::levels()).'.';
        }
        if (! in_array($subject, QuestionBankItem::SUBJECTS, true)) {
            return "{$prefix}Row {$rowNumber}: Subject must be Math, English, or Science.";
        }
        if ($question === '' || $a === '' || $b === '' || $c === '' || $d === '') {
            return "{$prefix}Row {$rowNumber}: Question text and all four options are required.";
        }
        if (! in_array($correct, ['A', 'B', 'C', 'D'], true)) {
            return "{$prefix}Row {$rowNumber}: Correct Option must be A, B, C, or D.";
        }

        return [
            'id' => $id,
            'attributes' => [
                'level' => $level,
                'subject' => $subject,
                'question_text' => $question,
                'option_a' => $a,
                'option_b' => $b,
                'option_c' => $c,
                'option_d' => $d,
                'correct_option' => $correct,
                'explanation' => null,
                'status' => 'Active',
            ],
        ];
    }

    /**
     * @return array<string, string|int>
     */
    protected function sampleRowArray(): array
    {
        return [
            'question_text' => 'What is 7 + 5?',
            'option_a' => '10',
            'option_b' => '11',
            'option_c' => '12',
            'option_d' => '13',
            'correct_option' => 'C',
        ];
    }

    /**
     * @param  list<string>  $items
     */
    protected function applyDropdown(Worksheet $sheet, string $range, array $items): void
    {
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
