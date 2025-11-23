<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dictionary Word Seeder
 * 
 * Purpose: Seeds the dictionary_words table with built-in English words
 * and definitions. These words will be used during video playback for
 * educational purposes.
 * 
 * Contains common English words across different difficulty levels.
 * Parents can add more words through the admin panel.
 */
class DictionaryWordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $words = [
            // Easy words - Common vocabulary for younger children
            ['word' => 'adventure', 'definition' => 'an exciting or dangerous experience', 'difficulty_level' => 'easy'],
            ['word' => 'curious', 'definition' => 'eager to know or learn something', 'difficulty_level' => 'easy'],
            ['word' => 'discover', 'definition' => 'find something or someone unexpectedly', 'difficulty_level' => 'easy'],
            ['word' => 'explore', 'definition' => 'travel through an unfamiliar area to learn about it', 'difficulty_level' => 'easy'],
            ['word' => 'wonder', 'definition' => 'a feeling of amazement and admiration', 'difficulty_level' => 'easy'],
            ['word' => 'brave', 'definition' => 'ready to face danger or pain', 'difficulty_level' => 'easy'],
            ['word' => 'wisdom', 'definition' => 'the quality of having experience and good judgment', 'difficulty_level' => 'easy'],
            ['word' => 'journey', 'definition' => 'an act of traveling from one place to another', 'difficulty_level' => 'easy'],
            ['word' => 'courage', 'definition' => 'the ability to do something that frightens one', 'difficulty_level' => 'easy'],
            ['word' => 'kindness', 'definition' => 'the quality of being friendly and considerate', 'difficulty_level' => 'easy'],
            ['word' => 'honest', 'definition' => 'free of deceit and untruthfulness', 'difficulty_level' => 'easy'],
            ['word' => 'respect', 'definition' => 'a feeling of deep admiration for someone', 'difficulty_level' => 'easy'],
            ['word' => 'patient', 'definition' => 'able to accept delays without becoming annoyed', 'difficulty_level' => 'easy'],
            ['word' => 'creative', 'definition' => 'relating to or involving imagination or original ideas', 'difficulty_level' => 'easy'],
            ['word' => 'friendly', 'definition' => 'kind and pleasant', 'difficulty_level' => 'easy'],
            ['word' => 'helpful', 'definition' => 'giving or ready to give help', 'difficulty_level' => 'easy'],
            ['word' => 'generous', 'definition' => 'showing a readiness to give more than is necessary', 'difficulty_level' => 'easy'],
            ['word' => 'confident', 'definition' => 'feeling or showing certainty about something', 'difficulty_level' => 'easy'],
            ['word' => 'determined', 'definition' => 'having made a firm decision and being resolved not to change it', 'difficulty_level' => 'easy'],
            ['word' => 'success', 'definition' => 'the accomplishment of an aim or purpose', 'difficulty_level' => 'easy'],
            
            // Medium words - Intermediate vocabulary
            ['word' => 'perseverance', 'definition' => 'persistence in doing something despite difficulty', 'difficulty_level' => 'medium'],
            ['word' => 'diligent', 'definition' => 'having or showing care in one\'s work or duties', 'difficulty_level' => 'medium'],
            ['word' => 'resourceful', 'definition' => 'having the ability to find quick and clever ways to overcome difficulties', 'difficulty_level' => 'medium'],
            ['word' => 'ambitious', 'definition' => 'having a strong desire to succeed', 'difficulty_level' => 'medium'],
            ['word' => 'innovative', 'definition' => 'featuring new methods or ideas', 'difficulty_level' => 'medium'],
            ['word' => 'resilient', 'definition' => 'able to recover quickly from difficulties', 'difficulty_level' => 'medium'],
            ['word' => 'diligence', 'definition' => 'careful and persistent work or effort', 'difficulty_level' => 'medium'],
            ['word' => 'integrity', 'definition' => 'the quality of being honest and having strong moral principles', 'difficulty_level' => 'medium'],
            ['word' => 'dedication', 'definition' => 'the quality of being committed to a task or purpose', 'difficulty_level' => 'medium'],
            ['word' => 'excellence', 'definition' => 'the quality of being outstanding or extremely good', 'difficulty_level' => 'medium'],
            ['word' => 'achievement', 'definition' => 'a thing done successfully with effort or skill', 'difficulty_level' => 'medium'],
            ['word' => 'knowledge', 'definition' => 'facts, information, and skills acquired through experience', 'difficulty_level' => 'medium'],
            ['word' => 'education', 'definition' => 'the process of receiving or giving systematic instruction', 'difficulty_level' => 'medium'],
            ['word' => 'intelligent', 'definition' => 'having or showing intelligence, especially of a high level', 'difficulty_level' => 'medium'],
            ['word' => 'thoughtful', 'definition' => 'showing consideration for the needs of other people', 'difficulty_level' => 'medium'],
            ['word' => 'responsible', 'definition' => 'having an obligation to do something', 'difficulty_level' => 'medium'],
            ['word' => 'organized', 'definition' => 'arranged in a systematic way', 'difficulty_level' => 'medium'],
            ['word' => 'focused', 'definition' => 'paying particular attention to something', 'difficulty_level' => 'medium'],
            ['word' => 'motivated', 'definition' => 'having a strong reason to act or accomplish something', 'difficulty_level' => 'medium'],
            ['word' => 'capable', 'definition' => 'having the ability, fitness, or quality necessary to do something', 'difficulty_level' => 'medium'],
            
            // Hard words - Advanced vocabulary
            ['word' => 'philanthropy', 'definition' => 'the desire to promote the welfare of others', 'difficulty_level' => 'hard'],
            ['word' => 'ephemeral', 'definition' => 'lasting for a very short time', 'difficulty_level' => 'hard'],
            ['word' => 'serendipity', 'definition' => 'the occurrence of pleasant things that happen by chance', 'difficulty_level' => 'hard'],
            ['word' => 'eloquent', 'definition' => 'fluent or persuasive in speaking or writing', 'difficulty_level' => 'hard'],
            ['word' => 'meticulous', 'definition' => 'showing great attention to detail', 'difficulty_level' => 'hard'],
            ['word' => 'perspicacious', 'definition' => 'having keen mental perception and understanding', 'difficulty_level' => 'hard'],
            ['word' => 'ubiquitous', 'definition' => 'present, appearing, or found everywhere', 'difficulty_level' => 'hard'],
            ['word' => 'voracious', 'definition' => 'wanting or devouring great quantities of food or knowledge', 'difficulty_level' => 'hard'],
            ['word' => 'magnanimous', 'definition' => 'generous in forgiving an insult or injury', 'difficulty_level' => 'hard'],
            ['word' => 'sagacious', 'definition' => 'having or showing keen mental discernment and good judgment', 'difficulty_level' => 'hard'],
            ['word' => 'diligent', 'definition' => 'having or showing care in one\'s work or duties', 'difficulty_level' => 'hard'],
            ['word' => 'profound', 'definition' => 'very great or intense', 'difficulty_level' => 'hard'],
            ['word' => 'ingenious', 'definition' => 'clever, original, and inventive', 'difficulty_level' => 'hard'],
            ['word' => 'tenacious', 'definition' => 'tending to keep a firm hold of something', 'difficulty_level' => 'hard'],
            ['word' => 'resilient', 'definition' => 'able to recover quickly from difficulties', 'difficulty_level' => 'hard'],
            ['word' => 'astute', 'definition' => 'having or showing an ability to accurately assess situations', 'difficulty_level' => 'hard'],
            ['word' => 'prudent', 'definition' => 'acting with or showing care and thought for the future', 'difficulty_level' => 'hard'],
            ['word' => 'diligent', 'definition' => 'having or showing care in one\'s work or duties', 'difficulty_level' => 'hard'],
            ['word' => 'erudite', 'definition' => 'having or showing great knowledge or learning', 'difficulty_level' => 'hard'],
            ['word' => 'perseverance', 'definition' => 'persistence in doing something despite difficulty', 'difficulty_level' => 'hard'],
        ];

        // Insert words into database
        foreach ($words as $word) {
            // Check if word already exists to avoid duplicates
            $exists = DB::table('dictionary_words')
                ->where('word', $word['word'])
                ->exists();

            if (!$exists) {
                DB::table('dictionary_words')->insert([
                    'word' => $word['word'],
                    'definition' => $word['definition'],
                    'difficulty_level' => $word['difficulty_level'],
                    'is_built_in' => true, // All seeded words are built-in
                    'user_id' => null, // Built-in words have no user
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

