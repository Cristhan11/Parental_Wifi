<?php

namespace App\Services;

use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Support\QuizSchoolLevel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
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

    private const QUIZ_TITLE_ROW = 4;

    private const META_ROW = 5;

    private const HEADER_ROW = 6;

    private const DATA_START_ROW = 7;

    private const SUBJECT_MAX_LENGTH = 191;

    private const QUIZ_TITLE_MAX_LENGTH = 255;

    /**
     * @return array{created:int,updated:int,errors:array<int,string>,pending_duplicate?:array{token:string,quiz_id:int,title:string}}
     */
    public function import(UploadedFile $file, int $userId, string $mode, ?int $quizId = null, ?int $confirmReplaceQuizId = null): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());

        $legacySheet = $spreadsheet->getSheetByName('Questions');
        if ($legacySheet && trim((string) $legacySheet->getCell('A1')->getValue()) === 'Question ID') {
            return $this->importLegacySheet($legacySheet, $userId, $mode);
        }

        return $this->importModernWorkbook($spreadsheet, $userId, $mode, $quizId, $confirmReplaceQuizId);
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
                break;
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
     * @return array{created:int,updated:int,errors:array<int,string>,pending_duplicate?:array{token:string,quiz_id:int,title:string}}
     */
    protected function importModernWorkbook(Spreadsheet $spreadsheet, int $userId, string $mode, ?int $quizId, ?int $confirmReplaceQuizId): array
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

        /** @var list<array{label:string,level:string,subject:string,title:string,rows:list<array{attributes:array<string,mixed>}>}> $prepared */
        $prepared = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if ($sheet->getSheetState() === Worksheet::SHEETSTATE_HIDDEN) {
                continue;
            }

            $sheetLabel = $sheet->getTitle();
            $parsed = $this->parseModernSheet($sheet);
            if ($parsed === null) {
                continue;
            }

            $level = $parsed['level'];
            $subject = $parsed['subject'];
            $quizTitleFromMeta = $parsed['quizTitle'];
            $headerRow = $parsed['headerRowIndex'];
            $columnMap = $parsed['columnMap'];

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

            $trimmedQuizTitle = trim($quizTitleFromMeta);
            if ($trimmedQuizTitle !== '' && mb_strlen($trimmedQuizTitle) > self::QUIZ_TITLE_MAX_LENGTH) {
                $errors[] = "Sheet \"{$sheetLabel}\": Quiz title must be ".self::QUIZ_TITLE_MAX_LENGTH.' characters or fewer.';

                continue;
            }

            $resolvedTitle = $this->resolveQuizTitleFromMeta($trimmedQuizTitle, $subject, $level);
            $rows = $sheet->toArray();
            $dataRows = array_slice($rows, $headerRow + 1);

            /** @var list<array{attributes:array<string,mixed>}> $rowPayloads */
            $rowPayloads = [];

            foreach ($dataRows as $offset => $row) {
                $rowNumber = $headerRow + $offset + 2;
                $iq = $columnMap['question'];
                if (trim((string) ($row[$iq] ?? '')) === '') {
                    break;
                }

                $payload = $this->validateModernRow(
                    $row,
                    $rowNumber,
                    $level,
                    $subject,
                    $columnMap,
                    "Sheet \"{$sheetLabel}\""
                );
                if (is_string($payload)) {
                    $errors[] = $payload;

                    continue;
                }

                $rowPayloads[] = ['attributes' => $payload['attributes']];
            }

            if ($rowPayloads === []) {
                continue;
            }

            $prepared[] = [
                'label' => $sheetLabel,
                'level' => $level,
                'subject' => $subject,
                'title' => $resolvedTitle,
                'rows' => $rowPayloads,
            ];
        }

        if ($errors !== []) {
            return compact('created', 'updated', 'errors');
        }

        if ($mode === 'add_new' && $confirmReplaceQuizId) {
            $confirmQuiz = Quiz::query()
                ->where('id', $confirmReplaceQuizId)
                ->where('user_id', $userId)
                ->first();
            if (! $confirmQuiz || $confirmQuiz->isRandomModeSettingsQuiz()) {
                return [
                    'created' => 0,
                    'updated' => 0,
                    'errors' => ['Invalid quiz selected for replace.'],
                ];
            }
            foreach ($prepared as $b) {
                if ($b['title'] !== $confirmQuiz->title) {
                    return [
                        'created' => 0,
                        'updated' => 0,
                        'errors' => ['When replacing a quiz, every sheet must use the quiz title "'.$confirmQuiz->title.'" in the Quiz title row.'],
                    ];
                }
            }
        }

        if ($prepared === []) {
            $errors[] = 'No question rows found. Use the downloaded template layout (instructions, quiz title row, school level and subject, header row, then data), or the legacy "Questions" sheet with Question ID in column A.';

            return compact('created', 'updated', 'errors');
        }

        if ($mode === 'add_new' && ! $confirmReplaceQuizId) {
            foreach ($prepared as $block) {
                $dup = $this->findDuplicateQuizByTitle($userId, $block['title']);
                if ($dup) {
                    $token = Str::random(48);

                    return [
                        'created' => 0,
                        'updated' => 0,
                        'errors' => [],
                        'pending_duplicate' => [
                            'token' => $token,
                            'quiz_id' => $dup->id,
                            'title' => $dup->title,
                        ],
                    ];
                }
            }
        }

        if ($mode === 'update_existing') {
            $replaceRows = [];
            foreach ($prepared as $block) {
                foreach ($block['rows'] as $r) {
                    $replaceRows[] = $r['attributes'];
                }
            }

            if ($replaceRows === []) {
                $errors[] = 'No question rows found for the selected quiz.';

                return compact('created', 'updated', 'errors');
            }

            DB::transaction(function () use ($userId, $targetQuiz, $replaceRows, &$created, &$updated): void {
                $scopedCount = QuestionBankItem::query()->where('quiz_id', $targetQuiz->id)->count();
                if ($scopedCount > 0) {
                    $deleted = QuestionBankItem::query()
                        ->where('quiz_id', $targetQuiz->id)
                        ->delete();
                } else {
                    $deleted = QuestionBankItem::query()
                        ->where('user_id', $userId)
                        ->whereNull('quiz_id')
                        ->where('level', $targetQuiz->level)
                        ->where('subject', $targetQuiz->subject)
                        ->delete();
                }

                $updated = (int) $deleted;
                $created = 0;

                foreach ($replaceRows as $attrs) {
                    QuestionBankItem::create(array_merge([
                        'user_id' => $userId,
                        'quiz_id' => $targetQuiz->id,
                    ], $attrs));
                    $created++;
                }

                $targetQuiz->update([
                    'question_count' => max(1, count($replaceRows)),
                    'questions' => ['questions' => []],
                ]);
            });

            return compact('created', 'updated', 'errors');
        }

        /** add_new */
        $replacedQuizIdsHandled = [];

        DB::transaction(function () use ($userId, $prepared, $confirmReplaceQuizId, &$created, &$updated, &$replacedQuizIdsHandled): void {
            foreach ($prepared as $block) {
                $title = $block['title'];
                $level = $block['level'];
                $subject = $block['subject'];
                $rowPayloads = $block['rows'];
                $dup = $this->findDuplicateQuizByTitle($userId, $title);

                if ($dup && $title === $dup->title) {
                    $quiz = $dup;
                    $shouldWipe = $confirmReplaceQuizId
                        && (int) $confirmReplaceQuizId === (int) $dup->id;
                    if ($shouldWipe && ! isset($replacedQuizIdsHandled[$quiz->id])) {
                        $removed = QuestionBankItem::query()->where('quiz_id', $quiz->id)->count();
                        QuestionBankItem::query()->where('quiz_id', $quiz->id)->delete();
                        $replacedQuizIdsHandled[$quiz->id] = true;
                        $updated += $removed;
                    }
                } else {
                    $quiz = Quiz::create([
                        'user_id' => $userId,
                        'title' => $title,
                        'description' => null,
                        'level' => $level,
                        'subject' => $subject,
                        'question_count' => max(1, count($rowPayloads)),
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

                foreach ($rowPayloads as $r) {
                    QuestionBankItem::create(array_merge([
                        'user_id' => $userId,
                        'quiz_id' => $quiz->id,
                    ], $r['attributes']));
                    $created++;
                }

                $quiz->update([
                    'question_count' => max(1, QuestionBankItem::query()->where('quiz_id', $quiz->id)->count()),
                ]);
            }
        });

        return compact('created', 'updated', 'errors');
    }

    protected function resolveQuizTitleFromMeta(string $trimmedQuizTitle, string $subject, string $level): string
    {
        $title = trim($trimmedQuizTitle);
        if ($title === '') {
            $title = trim($subject.' — '.$level);
        }
        if ($title === '') {
            $title = 'Imported quiz';
        }

        return $title;
    }

    protected function findDuplicateQuizByTitle(int $userId, string $title): ?Quiz
    {
        return Quiz::query()
            ->where('user_id', $userId)
            ->where('title', '!=', Quiz::RANDOM_MODE_SETTINGS_TITLE)
            ->where('title', $title)
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array{level:string,subject:string,quizTitle:string,headerRowIndex:int,columnMap:array<string,int|null>}|null
     */
    protected function parseModernSheet(Worksheet $sheet): ?array
    {
        $rows = $sheet->toArray();
        $headerRowIndex = null;

        foreach ($rows as $idx => $row) {
            $a = trim((string) ($row[0] ?? ''));
            if ($a === 'Question Text') {
                $headerRowIndex = $idx;
                break;
            }
        }

        if ($headerRowIndex === null) {
            return null;
        }

        $headerRow = $rows[$headerRowIndex] ?? [];
        $columnMap = $this->detectColumnIndexes($headerRow);

        $metaRowIdx = $headerRowIndex - 1;
        $meta = $rows[$metaRowIdx] ?? [];
        $quizTitleFromMeta = '';
        $quizRowIdx = $headerRowIndex - 2;
        if ($quizRowIdx >= 0) {
            $quizRow = $rows[$quizRowIdx] ?? [];
            $quizALabel = strtolower(trim((string) ($quizRow[0] ?? '')));
            if (str_contains($quizALabel, 'quiz title')) {
                $quizTitleFromMeta = trim((string) ($quizRow[1] ?? ''));
            }
        }
        if ($quizTitleFromMeta === '') {
            $quizTitleFromMeta = trim((string) ($meta[7] ?? ''));
        }

        $level = trim((string) ($meta[1] ?? ''));
        $subject = trim((string) ($meta[4] ?? ''));

        if ($subject === '' || $level === '') {
            $title = $sheet->getTitle();
            if (str_contains($title, ' — ')) {
                [$parsedSubject, $parsedLevel] = array_map('trim', explode(' — ', $title, 2));
                if ($subject === '' && $parsedSubject !== '') {
                    $subject = $parsedSubject;
                }
                if ($level === '' && in_array($parsedLevel, QuizSchoolLevel::levels(), true)) {
                    $level = $parsedLevel;
                }
            } elseif ($title !== '') {
                if ($subject === '') {
                    $subject = $title;
                }
            }
        }

        if ($level === '' || $subject === '') {
            return null;
        }

        return [
            'level' => $level,
            'subject' => $subject,
            'quizTitle' => $quizTitleFromMeta,
            'headerRowIndex' => $headerRowIndex,
            'columnMap' => $columnMap,
        ];
    }

    /**
     * @return array<string, int|null>
     */
    protected function detectColumnIndexes(array $headerRow): array
    {
        $byText = [];
        foreach ($headerRow as $i => $cell) {
            $key = strtolower(trim((string) $cell));
            if ($key !== '') {
                $byText[$key] = (int) $i;
            }
        }

        $hasType = isset($byText['question type']);

        if ($hasType) {
            return [
                'question' => $byText['question text'] ?? 0,
                'type' => $byText['question type'],
                'option_a' => $byText['option a'] ?? 2,
                'option_b' => $byText['option b'] ?? 3,
                'option_c' => $byText['option c'] ?? 4,
                'option_d' => $byText['option d'] ?? 5,
                'correct' => $byText['correct option'] ?? $byText['correct answer'] ?? 6,
                'bank_id' => $byText['bank id'] ?? null,
            ];
        }

        return [
            'question' => $byText['question text'] ?? 0,
            'type' => null,
            'option_a' => $byText['option a'] ?? 1,
            'option_b' => $byText['option b'] ?? 2,
            'option_c' => $byText['option c'] ?? 3,
            'option_d' => $byText['option d'] ?? 4,
            'correct' => $byText['correct option'] ?? 5,
            'bank_id' => $byText['bank id'] ?? null,
        ];
    }

    /**
     * Prefer quiz JSON questions when present (quizzes created/edited in the app); otherwise export the question bank.
     *
     * @return Collection<int, QuestionBankItem|array<string, mixed>>
     */
    protected function collectRowsForQuizExport(Quiz $quiz): Collection
    {
        $portalQuestions = $quiz->questions['questions'] ?? [];
        if (is_array($portalQuestions) && $portalQuestions !== []) {
            $rows = collect($portalQuestions)
                ->filter(fn (mixed $q): bool => is_array($q) && trim((string) ($q['question'] ?? '')) !== '')
                ->map(fn (array $q): array => $this->exportRowArrayFromPortalQuestion($q));

            if ($rows->isNotEmpty()) {
                return $rows;
            }
        }

        return QuestionBankItem::queryForFixedQuiz($quiz)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function exportRowArrayFromPortalQuestion(array $q): array
    {
        $type = $q['type'] ?? 'multiple_choice';
        if (! in_array($type, ['multiple_choice', 'true_false', 'fill_blank'], true)) {
            $type = 'multiple_choice';
        }

        $questionText = (string) ($q['question'] ?? '');
        /** @var list<string> $opts */
        $opts = array_values(array_map(fn (mixed $v): string => (string) $v, (array) ($q['options'] ?? [])));
        $correctAnswer = (string) ($q['correct_answer'] ?? '');

        if ($type === 'fill_blank') {
            return [
                'question_text' => $questionText,
                'question_type' => 'fill_blank',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_option' => $correctAnswer,
                'id' => null,
            ];
        }

        if ($type === 'true_false') {
            $a = $opts[0] ?? 'True';
            $b = $opts[1] ?? 'False';
            $letter = $this->resolveTrueFalseCorrectLetter($correctAnswer, $a, $b);

            return [
                'question_text' => $questionText,
                'question_type' => 'true_false',
                'option_a' => $a,
                'option_b' => $b,
                'option_c' => '',
                'option_d' => '',
                'correct_option' => $letter,
                'id' => null,
            ];
        }

        $correctLetter = $this->resolveMultipleChoiceCorrectLetter($correctAnswer, $opts);

        return [
            'question_text' => $questionText,
            'question_type' => 'multiple_choice',
            'option_a' => $opts[0] ?? '',
            'option_b' => $opts[1] ?? '',
            'option_c' => $opts[2] ?? '',
            'option_d' => $opts[3] ?? '',
            'correct_option' => $correctLetter,
            'id' => null,
        ];
    }

    protected function resolveMultipleChoiceCorrectLetter(string $correctAnswer, array $opts): string
    {
        $trimmed = trim($correctAnswer);
        if (preg_match('/^[ABCD]$/i', $trimmed) === 1) {
            return strtoupper($trimmed);
        }

        foreach (['A', 'B', 'C', 'D'] as $i => $letter) {
            $text = $opts[$i] ?? '';
            if ($text !== '' && strcasecmp($trimmed, trim($text)) === 0) {
                return $letter;
            }
        }

        return 'A';
    }

    protected function resolveTrueFalseCorrectLetter(string $correctAnswer, string $a, string $b): string
    {
        $trimmed = trim($correctAnswer);
        if (preg_match('/^[AB]$/i', $trimmed) === 1) {
            return strtoupper($trimmed);
        }
        if ($b !== '' && strcasecmp($trimmed, trim($b)) === 0) {
            return 'B';
        }
        if ($a !== '' && strcasecmp($trimmed, trim($a)) === 0) {
            return 'A';
        }

        return 'A';
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Quiz>  $quizzes
     */
    public function exportForQuizzes(\Illuminate\Support\Collection $quizzes, int $userId): StreamedResponse
    {
        $sheetsData = [];
        foreach ($quizzes as $quiz) {
            if ($quiz->isRandomModeSettingsQuiz() || ! $quiz->level || ! $quiz->subject) {
                continue;
            }
            $items = $this->collectRowsForQuizExport($quiz);
            $tabKey = $this->excelSheetTitle($quiz->title.'-'.$quiz->id);
            $sheetsData[$tabKey] = [
                $items,
                (string) $quiz->level,
                (string) $quiz->subject,
                (string) $quiz->title,
            ];
        }

        if ($sheetsData === []) {
            throw new \InvalidArgumentException('No quizzes to export.');
        }

        $firstQuiz = $quizzes->first(fn (Quiz $q): bool => ! $q->isRandomModeSettingsQuiz() && $q->level && $q->subject);
        $safeTitle = $firstQuiz
            ? (preg_replace('/[^\p{L}\p{N}\s_-]+/u', '', $firstQuiz->title) ?? 'quiz')
            : 'quiz';
        $safeTitle = substr(trim(str_replace(['/', '\\', '*', '[', ']', ':', '?'], '_', $safeTitle)), 0, 40);
        $filename = 'question-bank-'.($safeTitle !== '' ? $safeTitle : 'export').'.xlsx';

        return $this->buildMultiSubjectWorkbookDownload($sheetsData, $filename, includeBankIds: false);
    }

    public function template(): StreamedResponse
    {
        $samples = collect($this->sampleRowsForTemplate());
        $sheetsData = [
            'Question bank' => [$samples, 'Elementary', 'Math', 'My quiz title'],
        ];

        return $this->buildMultiSubjectWorkbookDownload($sheetsData, 'quiz-question-import-template.xlsx', includeBankIds: false);
    }

    /**
     * @param  array<string, array{0: Collection<int, QuestionBankItem|array<string, mixed>>, 1: string, 2: string, 3: string}>  $sheetsData  sheet key => [rows, levelLabel, subjectLabel, quizTitleForMeta]
     */
    protected function buildMultiSubjectWorkbookDownload(array $sheetsData, string $filename, bool $includeBankIds): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $first = true;

        foreach ($sheetsData as $subjectKey => [$rows, $levelLabel, $subjectLabel, $quizTitleForMeta]) {
            if ($first) {
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle($this->excelSheetTitle($subjectKey));
                $first = false;
            } else {
                $sheet = new Worksheet($spreadsheet, $this->excelSheetTitle($subjectKey));
                $spreadsheet->addSheet($sheet);
            }

            $this->fillSubjectSheet($sheet, $rows, $levelLabel, $subjectLabel, $quizTitleForMeta, $includeBankIds);
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
    protected function fillSubjectSheet(Worksheet $sheet, Collection $rows, string $levelLabel, string $subjectLabel, string $quizTitleForMeta, bool $includeBankIds): void
    {
        $lastColLetter = $includeBankIds ? 'H' : 'G';
        $instructionMergeEnd = $lastColLetter;
        $title = $this->instructionSheetTitle($subjectLabel, $includeBankIds);
        $bodyRich = $this->buildInstructionBodyRichText($includeBankIds);

        $sheet->mergeCells('A1:'.$instructionMergeEnd.'1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells('A2:'.$instructionMergeEnd.'3');
        $sheet->setCellValue('A2', $bodyRich);
        $sheet->getStyle('A2')->getFont()->setBold(false)->setSize(10);
        $sheet->getStyle('A2')->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);
        $sheet->getRowDimension(2)->setRowHeight(88);
        $sheet->getRowDimension(3)->setRowHeight(88);

        $sheet->setCellValue('A'.self::QUIZ_TITLE_ROW, 'Quiz title:');
        $sheet->mergeCells('B'.self::QUIZ_TITLE_ROW.':'.$instructionMergeEnd.self::QUIZ_TITLE_ROW);
        $sheet->setCellValue('B'.self::QUIZ_TITLE_ROW, $quizTitleForMeta);

        $sheet->setCellValue(self::META_LEVEL_LABEL_COL.self::META_ROW, 'School level:');
        $sheet->setCellValue(self::META_LEVEL_VALUE_COL.self::META_ROW, $levelLabel);
        $sheet->setCellValue(self::META_SUBJECT_LABEL_COL.self::META_ROW, 'Subject:');
        $sheet->setCellValue(self::META_SUBJECT_VALUE_COL.self::META_ROW, $subjectLabel);

        $metaBand = 'A'.self::QUIZ_TITLE_ROW.':'.$instructionMergeEnd.self::META_ROW;
        $sheet->getStyle($metaBand)->getFont()->setBold(true);
        $sheet->getStyle($metaBand)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');

        $this->applyDropdown($sheet, self::META_LEVEL_VALUE_COL.self::META_ROW.':'.self::META_LEVEL_VALUE_COL.self::META_ROW, QuizSchoolLevel::levels());

        $headers = [
            'Question Text',
            'Question Type',
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
        $headerRange = 'A'.self::HEADER_ROW.':'.$lastColLetter.self::HEADER_ROW;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFDE68A');
        $sheet->getStyle($headerRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FF64748B'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => 'FF334155'],
                ],
            ],
        ]);

        $excelRows = [];
        foreach ($rows as $row) {
            $storedType = (string) (data_get($row, 'question_type') ?: 'multiple_choice');
            $r = [
                data_get($row, 'question_text'),
                $this->excelQuestionTypeLabel($storedType),
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
        $typeChoices = ['Multiple choice', 'True/False', 'Fill in the blank'];
        $this->applyDropdown($sheet, 'B'.self::DATA_START_ROW.':B'.$lastDataRow, $typeChoices);

        if ($includeBankIds) {
            $sheet->getColumnDimension('H')->setWidth(12);
            $sheet->getStyle('H'.self::HEADER_ROW.':H'.$lastDataRow)->getFont()->getColor()->setARGB('FF6B7280');
        }
    }

    protected function excelQuestionTypeLabel(string $storedType): string
    {
        return match ($storedType) {
            'true_false' => 'True/False',
            'fill_blank' => 'Fill in the blank',
            default => 'Multiple choice',
        };
    }

    protected function instructionSheetTitle(string $subjectLabel, bool $includeBankIds): string
    {
        if (! $includeBankIds) {
            return 'Quiz import';
        }

        return 'Quiz import — '.$subjectLabel;
    }

    protected function buildInstructionBodyRichText(bool $includeBankIds): RichText
    {
        $r = self::QUIZ_TITLE_ROW;
        $m = self::META_ROW;

        $rich = new RichText;
        $head = $rich->createTextRun("Quick guide\n");
        $head->getFont()->setBold(true)->setSize(10);

        $rich->createText('1. ');
        $this->instructionBoldRun($rich, 'Quiz title');
        $rich->createText(" is the name shown in your quiz list. Importing again with the same title asks to replace that quiz's questions or cancel. Row {$r}.\n");

        $rich->createText('2. ');
        $this->instructionBoldRun($rich, 'School level');
        $rich->createText(': Select only from (Kindergarten, Elementary, High School, Senior High School). ');
        $this->instructionBoldRun($rich, "Row {$m}");
        $rich->createText(".\n");

        $rich->createText('3. ');
        $this->instructionBoldRun($rich, 'Subject');
        $rich->createText(' (any text, new names are saved as new subjects). ');
        $this->instructionBoldRun($rich, "Row {$m}");
        $rich->createText(".\n");

        $rich->createText("4. One question per row.\n");

        $rich->createText('5. Choose ');
        $this->instructionBoldRun($rich, 'Question Type');
        $rich->createText(': Multiple choice (four options A–D), True/False, or Fill in the blank (exact answer in Correct, ignores capitals). ');
        $this->instructionBoldRun($rich, 'Column B');
        $rich->createText(".\n");

        $rich->createText('6. Do not leave a blank ');
        $this->instructionBoldRun($rich, 'Question text');
        $rich->createText(" row: a blank row ends this quiz for import, and succeeding questions are not imported.\n");

        if (! $includeBankIds) {
            $rich->createText("\n");
            $this->instructionBoldRun($rich, 'Update existing');
            $rich->createText(' import: pick your quiz in the app; every question row in this file replaces the full question list for that quiz (old questions are removed). ');
        }

        if ($includeBankIds) {
            $rich->createText("\n");
            $rich->createText(
                'About the numbers in the Bank ID column: each number is just the app\'s tag so it can tell one saved question from another. '
                    .'If you change wording on a row that already had a number, keep that number on the row so the app updates the same question. '
                    .'If you add a brand-new question on a new row, leave that Bank ID cell empty so the app knows to add a new question instead of changing an old one.'
            );
        }

        return $rich;
    }

    protected function instructionBoldRun(RichText $rich, string $text): void
    {
        $run = $rich->createTextRun($text);
        $run->getFont()->setBold(true)->setSize(10);
    }

    /**
     * Excel worksheet names may not contain * : / \ ? [ ] and are limited to 31 characters.
     */
    protected function excelSheetTitle(string $subject): string
    {
        $invalid = ['*', ':', '/', '\\', '?', '[', ']'];
        $t = str_replace($invalid, '-', $subject);
        $t = trim($t);

        if (function_exists('mb_strlen') && mb_strlen($t) > 31) {
            $t = mb_substr($t, 0, 31);
        } elseif (strlen($t) > 31) {
            $t = substr($t, 0, 31);
        }

        $t = trim($t);

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
     * @param  array<string, int|null>  $columnMap
     * @return array{id:int|null,attributes:array<string,mixed>}|string
     */
    protected function validateModernRow(
        array $row,
        int $rowNumber,
        string $level,
        string $subject,
        array $columnMap,
        string $context
    ): array|string {
        $ix = fn (string $key): int => (int) ($columnMap[$key] ?? 0);
        $cell = fn (string $key): string => trim((string) ($row[$ix($key)] ?? ''));

        $question = $cell('question');
        $typeRaw = $columnMap['type'] !== null ? $cell('type') : '';
        $type = $this->normalizeQuestionTypeFromExcel($typeRaw);
        $a = $cell('option_a');
        $b = $cell('option_b');
        $c = $cell('option_c');
        $d = $cell('option_d');
        $correctRaw = $cell('correct');
        $bankIdKey = $columnMap['bank_id'];
        $idRaw = $bankIdKey !== null ? trim((string) ($row[$bankIdKey] ?? '')) : '';
        $id = $idRaw !== '' ? (int) $idRaw : null;

        $prefix = $context !== '' ? $context.' ' : '';

        if (! in_array($level, QuizSchoolLevel::levels(), true)) {
            return "{$prefix}Row {$rowNumber}: Level must be one of: ".implode(', ', QuizSchoolLevel::levels()).'.';
        }
        if ($subject === '') {
            return "{$prefix}Row {$rowNumber}: Subject is required.";
        }
        if (mb_strlen($subject) > self::SUBJECT_MAX_LENGTH) {
            return "{$prefix}Row {$rowNumber}: Subject must be ".self::SUBJECT_MAX_LENGTH.' characters or fewer.';
        }
        if ($question === '') {
            return "{$prefix}Row {$rowNumber}: Question text is required.";
        }

        if ($type === 'fill_blank') {
            if ($correctRaw === '') {
                return "{$prefix}Row {$rowNumber}: Correct Option must contain the expected answer for fill-in-the-blank.";
            }
            if (mb_strlen($correctRaw) > 255) {
                return "{$prefix}Row {$rowNumber}: Correct answer must be 255 characters or fewer.";
            }

            return [
                'id' => $id,
                'attributes' => [
                    'level' => $level,
                    'subject' => $subject,
                    'question_type' => 'fill_blank',
                    'question_text' => $question,
                    'option_a' => $a !== '' ? $a : '—',
                    'option_b' => $b !== '' ? $b : '—',
                    'option_c' => $c !== '' ? $c : '—',
                    'option_d' => $d !== '' ? $d : '—',
                    'correct_option' => $correctRaw,
                    'explanation' => null,
                    'status' => 'Active',
                ],
            ];
        }

        if ($type === 'true_false') {
            if ($a === '' || $b === '') {
                return "{$prefix}Row {$rowNumber}: True/False questions need Option A and Option B (for example True and False).";
            }
            $correctLetter = strtoupper($correctRaw);
            if (! in_array($correctLetter, ['A', 'B'], true)) {
                return "{$prefix}Row {$rowNumber}: True/False Correct Option must be A or B.";
            }

            return [
                'id' => $id,
                'attributes' => [
                    'level' => $level,
                    'subject' => $subject,
                    'question_type' => 'true_false',
                    'question_text' => $question,
                    'option_a' => $a,
                    'option_b' => $b,
                    'option_c' => $c !== '' ? $c : '—',
                    'option_d' => $d !== '' ? $d : '—',
                    'correct_option' => $correctLetter,
                    'explanation' => null,
                    'status' => 'Active',
                ],
            ];
        }

        $correct = strtoupper($correctRaw);
        if ($a === '' || $b === '' || $c === '' || $d === '') {
            return "{$prefix}Row {$rowNumber}: Multiple choice requires all four options.";
        }
        if (! in_array($correct, ['A', 'B', 'C', 'D'], true)) {
            return "{$prefix}Row {$rowNumber}: Correct Option must be A, B, C, or D.";
        }

        return [
            'id' => $id,
            'attributes' => [
                'level' => $level,
                'subject' => $subject,
                'question_type' => 'multiple_choice',
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

    protected function normalizeQuestionTypeFromExcel(string $raw): string
    {
        $t = strtolower(trim($raw));
        if ($t === '' || str_contains($t, 'multiple') || $t === 'mc') {
            return 'multiple_choice';
        }
        if (str_contains($t, 'true') || $t === 'tf') {
            return 'true_false';
        }
        if (str_contains($t, 'fill')) {
            return 'fill_blank';
        }

        return 'multiple_choice';
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
        if ($subject === '') {
            return "{$prefix}Row {$rowNumber}: Subject is required.";
        }
        if (mb_strlen($subject) > self::SUBJECT_MAX_LENGTH) {
            return "{$prefix}Row {$rowNumber}: Subject must be ".self::SUBJECT_MAX_LENGTH.' characters or fewer.';
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
                'question_type' => 'multiple_choice',
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
     * @return list<array<string, mixed>>
     */
    protected function sampleRowsForTemplate(): array
    {
        return [
            [
                'question_text' => 'What is 7 + 5?',
                'question_type' => 'multiple_choice',
                'option_a' => '10',
                'option_b' => '11',
                'option_c' => '12',
                'option_d' => '13',
                'correct_option' => 'C',
            ],
            [
                'question_text' => 'The sky is blue.',
                'question_type' => 'true_false',
                'option_a' => 'True',
                'option_b' => 'False',
                'option_c' => '',
                'option_d' => '',
                'correct_option' => 'A',
            ],
            [
                'question_text' => 'Capital of France is _____.',
                'question_type' => 'fill_blank',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_option' => 'Paris',
            ],
        ];
    }

    /**
     * Read built-in quiz Excel workbooks from a directory (used by database seeders).
     *
     * @return list<array{
     *     source_file: string,
     *     quiz_title: string,
     *     level: string,
     *     subject: string,
     *     items: list<array<string, mixed>>
     * }>
     */
    public function readSeedBlocksFromDirectory(string $directory): array
    {
        $path = rtrim($directory, '/\\');
        $files = glob($path.DIRECTORY_SEPARATOR.'*.xlsx') ?: [];
        sort($files);

        /** @var list<array{source_file: string, quiz_title: string, level: string, subject: string, items: list<array<string, mixed>>}> $blocks */
        $blocks = [];

        foreach ($files as $filePath) {
            $spreadsheet = IOFactory::load($filePath);
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                if ($sheet->getSheetState() === Worksheet::SHEETSTATE_HIDDEN) {
                    continue;
                }

                $parsed = $this->parseModernSheet($sheet);
                if ($parsed === null) {
                    continue;
                }

                $level = $parsed['level'];
                $subject = $parsed['subject'];
                if (! in_array($level, QuizSchoolLevel::levels(), true)) {
                    throw new \RuntimeException(
                        'Invalid school level in '.basename($filePath).': '.$level
                    );
                }

                $quizTitle = $this->resolveQuizTitleFromMeta(
                    trim($parsed['quizTitle']),
                    $subject,
                    $level
                );

                $rows = $sheet->toArray();
                $dataRows = array_slice($rows, $parsed['headerRowIndex'] + 1);
                $columnMap = $parsed['columnMap'];
                $sheetLabel = $sheet->getTitle();

                /** @var list<array<string, mixed>> $items */
                $items = [];

                foreach ($dataRows as $offset => $row) {
                    $rowNumber = $parsed['headerRowIndex'] + $offset + 2;
                    $iq = $columnMap['question'];
                    if (trim((string) ($row[$iq] ?? '')) === '') {
                        break;
                    }

                    $payload = $this->validateModernRow(
                        $row,
                        $rowNumber,
                        $level,
                        $subject,
                        $columnMap,
                        "File \"".basename($filePath)."\" sheet \"{$sheetLabel}\""
                    );

                    if (is_string($payload)) {
                        throw new \RuntimeException($payload);
                    }

                    $attributes = $payload['attributes'];
                    $attributes['source_competency'] = $quizTitle;
                    $items[] = $attributes;
                }

                if ($items === []) {
                    continue;
                }

                $blocks[] = [
                    'source_file' => basename($filePath),
                    'quiz_title' => $quizTitle,
                    'level' => $level,
                    'subject' => $subject,
                    'items' => $items,
                ];
            }
        }

        if ($blocks === []) {
            throw new \RuntimeException('No quiz seed blocks found in: '.$path);
        }

        return $blocks;
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
