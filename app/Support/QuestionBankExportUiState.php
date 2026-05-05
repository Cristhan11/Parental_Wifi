<?php

namespace App\Support;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Arr;

/**
 * Builds view props for the question bank Excel export form (quizzes import page).
 */
final class QuestionBankExportUiState
{
    /**
     * @return array{
     *     exportQuizzesPayload: list<array{id:int,title:string,level:?string,subject:?string}>,
     *     exportQuizLevels: list<string>,
     *     initialExportLevel: string,
     *     initialExportQuizIds: list<int>
     * }
     */
    public static function forUser(User $user): array
    {
        $exportableQuizzes = $user->quizzes()
            ->orderByRaw('CASE WHEN title = ? THEN 1 ELSE 0 END', [Quiz::RANDOM_MODE_SETTINGS_TITLE])
            ->orderBy('title')
            ->get();

        $exportQuizzesPayload = $exportableQuizzes
            ->reject(fn (Quiz $q): bool => $q->isRandomModeSettingsQuiz())
            ->map(fn (Quiz $q): array => [
                'id' => $q->id,
                'title' => $q->title,
                'level' => $q->level,
                'subject' => $q->subject,
            ])->values()->all();

        $levelHasQuizzes = function (string $level) use ($exportQuizzesPayload): bool {
            foreach ($exportQuizzesPayload as $q) {
                if (($q['level'] ?? null) === $level) {
                    return true;
                }
            }

            return false;
        };

        $initialExportLevel = old('export_level');
        if (! is_string($initialExportLevel) || ! in_array($initialExportLevel, QuizSchoolLevel::levels(), true)) {
            $initialExportLevel = null;
        }
        if ($initialExportLevel === null || ! $levelHasQuizzes($initialExportLevel)) {
            $initialExportLevel = null;
            foreach (QuizSchoolLevel::levels() as $lvl) {
                if ($levelHasQuizzes($lvl)) {
                    $initialExportLevel = $lvl;
                    break;
                }
            }
        }
        if ($initialExportLevel === null) {
            $initialExportLevel = QuizSchoolLevel::levels()[0] ?? 'Elementary';
        }

        $initialExportQuizIds = collect(Arr::wrap(old('quiz_ids', [])))
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->values()
            ->all();

        return [
            'exportQuizzesPayload' => $exportQuizzesPayload,
            'exportQuizLevels' => QuizSchoolLevel::levels(),
            'initialExportLevel' => $initialExportLevel,
            'initialExportQuizIds' => $initialExportQuizIds,
        ];
    }
}
