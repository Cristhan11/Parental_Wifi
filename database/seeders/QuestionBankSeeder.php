<?php

namespace Database\Seeders;

use App\Models\QuestionBankItem;
use App\Services\QuestionBankExcelService;
use Illuminate\Database\Seeder;

class QuestionBankSeeder extends Seeder
{
    public function run(): void
    {
        $directory = database_path('seed-data/quiz-excel');
        $blocks = app(QuestionBankExcelService::class)->readSeedBlocksFromDirectory($directory);

        $created = 0;

        foreach ($blocks as $block) {
            foreach ($block['items'] as $attributes) {
                QuestionBankItem::create(array_merge([
                    'user_id' => null,
                    'quiz_id' => null,
                ], $attributes));
                $created++;
            }
        }

        $this->command?->info("QuestionBankSeeder: seeded {$created} questions from ".count($blocks).' workbook(s).');
    }
}
