<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use JsonException;

/**
 * Dictionary Word Seeder
 *
 * Loads built-in words from Merriam-Webster's Vocabulary Builder (second edition),
 * extracted from the publisher PDF into database/data/mw_vocabulary_builder_reference.json.
 * Regenerate that file with: python scripts/extract_mw_vocabulary_builder.py
 *
 * Skips rows whose word already exists (case per DB collation). Does not delete
 * legacy rows; truncate dictionary_words first only if you intend a full reset.
 */
class DictionaryWordSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/mw_vocabulary_builder_reference.json');

        if (! is_readable($path)) {
            $this->command?->warn('Missing '.$path.' — using minimal fallback vocabulary.');
            $this->seedFallback();

            return;
        }

        try {
            /** @var array{entries?: array<int, array{word?: string, definition?: string}>} $payload */
            $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->command?->error('Invalid dictionary JSON: '.$e->getMessage());
            $this->seedFallback();

            return;
        }

        $entries = $payload['entries'] ?? [];
        $now = now();
        $batch = [];

        foreach ($entries as $row) {
            $word = isset($row['word']) ? trim((string) $row['word']) : '';
            $definition = isset($row['definition']) ? trim((string) $row['definition']) : '';

            if ($word === '' || $definition === '') {
                continue;
            }

            $batch[] = [
                'word' => $word,
                'definition' => $definition,
                'is_built_in' => true,
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 150) {
                DB::table('dictionary_words')->insertOrIgnore($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::table('dictionary_words')->insertOrIgnore($batch);
        }
    }

    /**
     * Tiny vocabulary if the MW JSON is absent (e.g. fresh checkout before running the extractor).
     */
    private function seedFallback(): void
    {
        $words = [
            ['word' => 'adventure', 'definition' => 'an exciting or dangerous experience'],
            ['word' => 'curious', 'definition' => 'eager to know or learn something'],
            ['word' => 'discover', 'definition' => 'find something unexpectedly'],
            ['word' => 'explore', 'definition' => 'travel through an unfamiliar area to learn about it'],
            ['word' => 'perseverance', 'definition' => 'persistence despite difficulty'],
        ];

        foreach ($words as $word) {
            $exists = DB::table('dictionary_words')
                ->where('word', $word['word'])
                ->exists();

            if (! $exists) {
                DB::table('dictionary_words')->insert([
                    'word' => $word['word'],
                    'definition' => $word['definition'],
                    'is_built_in' => true,
                    'user_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
