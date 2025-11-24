<?php

/**
 * QuizTestDataSeeder - Test Data for Quiz System
 * 
 * This seeder creates sample data for testing the quiz system frontend.
 * It's used during development to quickly set up a test environment.
 * 
 * What it creates:
 * 1. Test parent user (parent@test.com / password)
 * 2. Test device with MAC address (AA:BB:CC:DD:EE:FF)
 * 3. 4 sample quizzes with different question types:
 *    - Math Quiz (Multiple Choice)
 *    - Geography Quiz (Fill-in-the-Blank)
 *    - Science Quiz (True/False)
 *    - General Knowledge Quiz (Mixed Types)
 * 4. Links device to quizzes (assigns quizzes to device)
 * 
 * Usage:
 * - php artisan db:seed --class=QuizTestDataSeeder
 * - php artisan quiz:setup-test-data
 * 
 * Why firstOrCreate? Prevents duplicate data if seeder runs multiple times.
 * If data already exists, it uses existing records instead of creating duplicates.
 */

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class QuizTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This method is called automatically when running the seeder.
     * It creates all test data in the correct order (user → device → quizzes → assignments).
     * 
     * @return void
     */
    public function run(): void
    {
        // Step 1: Create or get test parent user
        // firstOrCreate() checks if user exists (by email), creates if not
        // This prevents duplicate users if seeder runs multiple times
        $parent = User::firstOrCreate(
            ['email' => 'parent@test.com'],
            [
                'name' => 'Test Parent',
                'password' => Hash::make('password'),
                'role' => 'parent',
            ]
        );

        // Create or get test device
        $device = Device::firstOrCreate(
            ['mac_address' => 'AA:BB:CC:DD:EE:FF'],
            [
                'user_id' => $parent->id,
                'name' => 'Test Device',
                'status' => 'active',
                'remaining_time_minutes' => 0, // No time left, will trigger portal
                'total_time_allocated' => 0,
            ]
        );

        // Create Quiz 1: Multiple Choice (Math Quiz)
        $quiz1 = Quiz::firstOrCreate(
            [
                'user_id' => $parent->id,
                'title' => 'Math Quiz - Basic Addition',
            ],
            [
                'description' => 'Test your basic math skills with addition problems.',
                'passing_score' => 70,
                'time_reward_minutes' => 15,
                'is_active' => true,
                'questions' => [
                    'questions' => [
                        [
                            'id' => 1,
                            'question' => 'What is 2 + 2?',
                            'type' => 'multiple_choice',
                            'options' => ['2', '3', '4', '5'],
                            'correct_answer' => '4',
                        ],
                        [
                            'id' => 2,
                            'question' => 'What is 5 + 3?',
                            'type' => 'multiple_choice',
                            'options' => ['6', '7', '8', '9'],
                            'correct_answer' => '8',
                        ],
                        [
                            'id' => 3,
                            'question' => 'What is 10 + 5?',
                            'type' => 'multiple_choice',
                            'options' => ['12', '13', '14', '15'],
                            'correct_answer' => '15',
                        ],
                        [
                            'id' => 4,
                            'question' => 'What is 7 + 6?',
                            'type' => 'multiple_choice',
                            'options' => ['11', '12', '13', '14'],
                            'correct_answer' => '13',
                        ],
                    ],
                ],
            ]
        );

        // Create Quiz 2: Fill-in-the-Blank (Geography Quiz)
        $quiz2 = Quiz::firstOrCreate(
            [
                'user_id' => $parent->id,
                'title' => 'Geography Quiz - Capitals',
            ],
            [
                'description' => 'Fill in the blanks with the correct capital cities.',
                'passing_score' => 60,
                'time_reward_minutes' => 20,
                'is_active' => true,
                'questions' => [
                    'questions' => [
                        [
                            'id' => 1,
                            'question' => 'The capital of France is ___.',
                            'type' => 'fill_blank',
                            'correct_answer' => 'Paris',
                        ],
                        [
                            'id' => 2,
                            'question' => 'The capital of Japan is ___.',
                            'type' => 'fill_blank',
                            'correct_answer' => 'Tokyo',
                        ],
                        [
                            'id' => 3,
                            'question' => 'The capital of Australia is ___.',
                            'type' => 'fill_blank',
                            'correct_answer' => 'Canberra',
                        ],
                    ],
                ],
            ]
        );

        // Create Quiz 3: True/False (Science Quiz)
        $quiz3 = Quiz::firstOrCreate(
            [
                'user_id' => $parent->id,
                'title' => 'Science Quiz - True or False',
            ],
            [
                'description' => 'Answer true or false to these science questions.',
                'passing_score' => 75,
                'time_reward_minutes' => 10,
                'is_active' => true,
                'questions' => [
                    'questions' => [
                        [
                            'id' => 1,
                            'question' => 'The Earth revolves around the Sun.',
                            'type' => 'true_false',
                            'options' => ['True', 'False'],
                            'correct_answer' => 'True',
                        ],
                        [
                            'id' => 2,
                            'question' => 'Water boils at 100 degrees Celsius.',
                            'type' => 'true_false',
                            'options' => ['True', 'False'],
                            'correct_answer' => 'True',
                        ],
                        [
                            'id' => 3,
                            'question' => 'The human body has 206 bones.',
                            'type' => 'true_false',
                            'options' => ['True', 'False'],
                            'correct_answer' => 'True',
                        ],
                    ],
                ],
            ]
        );

        // Create Quiz 4: Mixed Types (General Knowledge)
        $quiz4 = Quiz::firstOrCreate(
            [
                'user_id' => $parent->id,
                'title' => 'General Knowledge Quiz',
            ],
            [
                'description' => 'A mix of different question types to test your knowledge.',
                'passing_score' => 80,
                'time_reward_minutes' => 25,
                'is_active' => true,
                'questions' => [
                    'questions' => [
                        [
                            'id' => 1,
                            'question' => 'What is the largest planet in our solar system?',
                            'type' => 'multiple_choice',
                            'options' => ['Earth', 'Mars', 'Jupiter', 'Saturn'],
                            'correct_answer' => 'Jupiter',
                        ],
                        [
                            'id' => 2,
                            'question' => 'The Great Wall of China is located in ___.',
                            'type' => 'fill_blank',
                            'correct_answer' => 'China',
                        ],
                        [
                            'id' => 3,
                            'question' => 'Sharks are mammals.',
                            'type' => 'true_false',
                            'options' => ['True', 'False'],
                            'correct_answer' => 'False',
                        ],
                        [
                            'id' => 4,
                            'question' => 'How many continents are there?',
                            'type' => 'multiple_choice',
                            'options' => ['5', '6', '7', '8'],
                            'correct_answer' => '7',
                        ],
                        [
                            'id' => 5,
                            'question' => 'The smallest country in the world is ___.',
                            'type' => 'fill_blank',
                            'correct_answer' => 'Vatican City',
                        ],
                    ],
                ],
            ]
        );

        // Create Quiz 5: Inactive Quiz (for testing)
        $quiz5 = Quiz::firstOrCreate(
            [
                'user_id' => $parent->id,
                'title' => 'Inactive Quiz (Test)',
            ],
            [
                'description' => 'This quiz is inactive and should not appear in portal.',
                'passing_score' => 70,
                'time_reward_minutes' => 15,
                'is_active' => false, // Inactive
                'questions' => [
                    'questions' => [
                        [
                            'id' => 1,
                            'question' => 'This is a test question.',
                            'type' => 'multiple_choice',
                            'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                            'correct_answer' => 'Option A',
                        ],
                    ],
                ],
            ]
        );

        // Assign quizzes to device (except inactive quiz)
        $device->quizzes()->syncWithoutDetaching([
            $quiz1->id,
            $quiz2->id,
            $quiz3->id,
            $quiz4->id,
        ]);

        $this->command->info('✅ Test data created successfully!');
        $this->command->info('📧 Parent Login: parent@test.com / password');
        $this->command->info('📱 Device MAC: AA:BB:CC:DD:EE:FF');
        $this->command->info('📝 Quizzes created: ' . Quiz::where('user_id', $parent->id)->count());
    }
}

