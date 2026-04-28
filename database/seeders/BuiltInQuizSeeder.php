<?php

namespace Database\Seeders;

use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;

class BuiltInQuizSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()
            ->where('email', 'admin123@email.com')
            ->orWhere('role', User::ROLE_ADMIN)
            ->orWhere('role', User::ROLE_PARENT_ADMIN)
            ->orWhere('role', User::ROLE_PARENT)
            ->orderBy('id')
            ->first();

        if (! $owner) {
            $this->command?->warn('BuiltInQuizSeeder skipped: default Parent Owner account was not found.');

            return;
        }

        $levels = ['Elementary', 'High School', 'Senior High School'];
        $subjects = ['Math', 'English', 'Science'];

        foreach ($levels as $level) {
            foreach ($subjects as $subject) {
                $availableItems = QuestionBankItem::query()
                    ->where('level', $level)
                    ->where('subject', $subject)
                    ->where('status', 'Active')
                    ->count();

                if ($availableItems === 0) {
                    continue;
                }

                $questionCount = min(10, $availableItems);
                $title = "{$level} {$subject} Starter Quiz";
                $questions = QuestionBankItem::query()
                    ->where('level', $level)
                    ->where('subject', $subject)
                    ->where('status', 'Active')
                    ->inRandomOrder()
                    ->limit($questionCount)
                    ->get()
                    ->values()
                    ->map(function (QuestionBankItem $item, int $index): array {
                        $options = [
                            (string) $item->option_a,
                            (string) $item->option_b,
                            (string) $item->option_c,
                            (string) $item->option_d,
                        ];

                        $correctMap = [
                            'A' => 0,
                            'B' => 1,
                            'C' => 2,
                            'D' => 3,
                        ];
                        $correctIndex = $correctMap[strtoupper((string) $item->correct_option)] ?? 0;

                        return [
                            'id' => $index + 1,
                            'question' => (string) $item->question_text,
                            'type' => 'multiple_choice',
                            'options' => $options,
                            'correct_answer' => $options[$correctIndex] ?? $options[0],
                        ];
                    })
                    ->all();

                Quiz::updateOrCreate(
                    [
                        'user_id' => $owner->id,
                        'title' => $title,
                    ],
                    [
                        'description' => "Built-in {$subject} quiz for {$level}. Questions are drawn randomly from the question bank.",
                        'level' => $level,
                        'subject' => $subject,
                        'question_count' => $questionCount,
                        'scoring_mode' => 'pass_score',
                        'minutes_per_correct' => 1,
                        'passing_score' => 70,
                        'time_reward_minutes' => 15,
                        'max_passes_per_day' => null,
                        'retry_cooldown_minutes' => null,
                        'questions' => ['questions' => $questions],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
