<?php

namespace Database\Seeders;

use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Models\User;
use App\Services\QuestionBankExcelService;
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

        $directory = database_path('seed-data/quiz-excel');
        $blocks = app(QuestionBankExcelService::class)->readSeedBlocksFromDirectory($directory);
        $quizCount = 0;

        foreach ($blocks as $block) {
            $bankCount = count($block['items']);
            $questionsPerAttempt = min(15, max(1, $bankCount));

            $quiz = Quiz::updateOrCreate(
                [
                    'user_id' => $owner->id,
                    'title' => $block['quiz_title'],
                ],
                [
                    'description' => "Built-in quiz seeded from {$block['source_file']}.",
                    'level' => $block['level'],
                    'subject' => $block['subject'],
                    'question_count' => $questionsPerAttempt,
                    'scoring_mode' => 'pass_score',
                    'minutes_per_correct' => 1,
                    'passing_score' => 70,
                    'time_reward_minutes' => 15,
                    'max_passes_per_day' => null,
                    'retry_cooldown_minutes' => null,
                    'questions' => ['questions' => []],
                    'is_active' => true,
                ]
            );

            QuestionBankItem::query()
                ->whereNull('user_id')
                ->whereNull('quiz_id')
                ->where('level', $block['level'])
                ->where('subject', $block['subject'])
                ->where('source_competency', $block['quiz_title'])
                ->update(['quiz_id' => $quiz->id]);

            $quizCount++;
        }

        $this->command?->info("BuiltInQuizSeeder: created or updated {$quizCount} default quiz(zes) from Excel seed data.");
    }
}
