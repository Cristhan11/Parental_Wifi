<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionBankItem extends Model
{
    use HasFactory;

    public const SUBJECTS = ['Math', 'English', 'Science'];

    public const STATUSES = ['Active', 'Inactive'];

    public const QUESTION_TYPES = ['multiple_choice', 'true_false', 'fill_blank'];

    protected $fillable = [
        'user_id',
        'quiz_id',
        'level',
        'subject',
        'question_type',
        'question_text',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_option',
        'explanation',
        'status',
        'source_competency',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Active bank rows for a fixed (level + subject) quiz: scoped by quiz_id when any exist, else legacy shared pool.
     */
    public static function queryForFixedQuiz(Quiz $quiz): Builder
    {
        $q = static::query()
            ->where('level', $quiz->level)
            ->where('subject', $quiz->subject)
            ->where('status', 'Active')
            ->where(function (Builder $b) use ($quiz): void {
                $b->where('user_id', $quiz->user_id)->orWhereNull('user_id');
            });

        if (static::query()->where('quiz_id', $quiz->id)->exists()) {
            return $q->where('quiz_id', $quiz->id);
        }

        return $q->whereNull('quiz_id');
    }

    /**
     * Active bank rows for Random Quiz Mode (time_reward): matches school levels and includes
     * both legacy global rows (quiz_id null) and per-quiz imports (quiz_id set), plus built-ins (user_id null).
     *
     * @param  list<string>  $levels
     */
    public static function queryForRandomBankMix(Quiz $randomSettingsQuiz, array $levels): Builder
    {
        $userId = (int) $randomSettingsQuiz->user_id;

        return static::query()
            ->where('status', 'Active')
            ->whereIn('level', $levels)
            ->where(function (Builder $q) use ($userId): void {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            });
    }

    /**
     * Shape used by the child portal and quiz editor for one bank-backed question.
     *
     * @return array{id:int,question:string,type:string,options:array<int,string>,correct_answer:string}
     */
    public function toPortalQuestionPayload(int $index): array
    {
        $type = $this->question_type ?: 'multiple_choice';

        if ($type === 'true_false') {
            $correctLetter = strtoupper((string) $this->correct_option);

            return [
                'id' => $index + 1,
                'question' => (string) $this->question_text,
                'type' => 'true_false',
                'options' => [(string) $this->option_a, (string) $this->option_b],
                'correct_answer' => $correctLetter === 'B'
                    ? (string) $this->option_b
                    : (string) $this->option_a,
            ];
        }

        if ($type === 'fill_blank') {
            return [
                'id' => $index + 1,
                'question' => (string) $this->question_text,
                'type' => 'fill_blank',
                'options' => [],
                'correct_answer' => (string) $this->correct_option,
            ];
        }

        return [
            'id' => $index + 1,
            'question' => (string) $this->question_text,
            'type' => 'multiple_choice',
            'options' => [
                (string) $this->option_a,
                (string) $this->option_b,
                (string) $this->option_c,
                (string) $this->option_d,
            ],
            'correct_answer' => (string) $this->correct_option,
        ];
    }
}
