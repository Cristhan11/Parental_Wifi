<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_update_persists_metadata_and_questions(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Original',
            'description' => 'Old desc',
            'passing_score' => 50,
            'time_reward_minutes' => 15,
            'max_passes_per_day' => null,
            'retry_cooldown_minutes' => null,
            'questions' => [
                'questions' => [
                    [
                        'id' => 1,
                        'question' => 'Q1?',
                        'type' => 'true_false',
                        'options' => ['True', 'False'],
                        'correct_answer' => 'True',
                    ],
                ],
            ],
            'is_active' => true,
        ]);

        $payload = [
            'title' => 'Updated Title',
            'description' => 'New description text',
            'passing_score' => 60,
            'time_reward_minutes' => 20,
            'max_passes_per_day' => 2,
            'retry_cooldown_minutes' => 30,
            'is_active' => '1',
            'questions' => [
                [
                    'question' => 'What is 2+2?',
                    'type' => 'multiple_choice',
                    'options' => ['3', '4', '5', '6'],
                    'correct_answer' => '4',
                ],
                [
                    'question' => 'Capital of France?',
                    'type' => 'fill_blank',
                    'options' => [],
                    'correct_answer' => 'Paris',
                ],
            ],
        ];

        $response = $this->actingAs($user)->put(route('quizzes.update', $quiz), $payload);

        $response->assertRedirect(route('quizzes.index'));
        $response->assertSessionHas('success');

        $quiz->refresh();
        $this->assertSame('Updated Title', $quiz->title);
        $this->assertSame('New description text', $quiz->description);
        $this->assertSame(60, $quiz->passing_score);
        $this->assertSame(20, $quiz->time_reward_minutes);
        $this->assertSame(2, $quiz->max_passes_per_day);
        $this->assertSame(30, $quiz->retry_cooldown_minutes);
        $this->assertTrue($quiz->is_active);
        $this->assertCount(2, $quiz->questions['questions']);
        $this->assertSame('What is 2+2?', $quiz->questions['questions'][0]['question']);
        $this->assertSame('Paris', $quiz->questions['questions'][1]['correct_answer']);
    }

    /**
     * True/false must not POST four option slots (empty C/D broke validation before fix).
     */
    public function test_quiz_update_accepts_true_false_with_only_two_options(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'TF Quiz',
            'description' => null,
            'passing_score' => 50,
            'time_reward_minutes' => 15,
            'max_passes_per_day' => null,
            'retry_cooldown_minutes' => null,
            'questions' => [
                'questions' => [
                    [
                        'id' => 1,
                        'question' => 'Sky is blue?',
                        'type' => 'true_false',
                        'options' => ['True', 'False'],
                        'correct_answer' => 'True',
                    ],
                ],
            ],
            'is_active' => true,
        ]);

        $payload = [
            'title' => 'TF Quiz',
            'description' => 'Updated',
            'passing_score' => 50,
            'time_reward_minutes' => 15,
            'is_active' => '1',
            'questions' => [
                [
                    'question' => 'Sky is blue?',
                    'type' => 'true_false',
                    'options' => ['True', 'False'],
                    'correct_answer' => 'True',
                ],
                [
                    'question' => '2 + 2 = 4',
                    'type' => 'true_false',
                    'options' => ['True', 'False'],
                    'correct_answer' => 'True',
                ],
            ],
        ];

        $response = $this->actingAs($user)->put(route('quizzes.update', $quiz), $payload);

        $response->assertRedirect(route('quizzes.index'));
        $quiz->refresh();
        $this->assertCount(2, $quiz->questions['questions']);
        $this->assertSame('2 + 2 = 4', $quiz->questions['questions'][1]['question']);
    }

    public function test_portal_quiz_blocked_when_retry_cooldown_active(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id, 'role' => 'child']);
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Cooldown Quiz',
            'description' => null,
            'passing_score' => 50,
            'time_reward_minutes' => 10,
            'max_passes_per_day' => null,
            'retry_cooldown_minutes' => 120,
            'questions' => [
                'questions' => [
                    [
                        'id' => 1,
                        'question' => 'T?',
                        'type' => 'true_false',
                        'options' => ['True', 'False'],
                        'correct_answer' => 'True',
                    ],
                ],
            ],
            'is_active' => true,
        ]);
        $quiz->devices()->sync([$device->id]);

        QuizAttempt::create([
            'device_id' => $device->id,
            'quiz_id' => $quiz->id,
            'answers' => ['answers' => []],
            'score' => 0,
            'passed' => false,
            'completed_at' => now(),
        ]);

        $response = $this->get(route('portal.quiz.show', ['quiz' => $quiz, 'mac' => $device->mac_address]));

        $response->assertRedirect(route('portal.landing', ['mac' => $device->mac_address]));
        $response->assertSessionHas('error');
    }

    public function test_quiz_create_form_only_lists_child_role_devices(): void
    {
        $user = User::factory()->create();
        Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
            'name' => 'ChildOnlyQuizList',
        ]);
        Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'parent',
            'name' => 'ParentHiddenFromQuizList',
        ]);

        $response = $this->actingAs($user)->get(route('quizzes.create'));

        $response->assertOk();
        $response->assertSee('ChildOnlyQuizList');
        $response->assertDontSee('ParentHiddenFromQuizList');
    }

    public function test_quiz_store_assigns_only_submitted_child_device(): void
    {
        $user = User::factory()->create();
        $child = Device::factory()->create(['user_id' => $user->id, 'role' => 'child']);

        $payload = [
            'title' => 'Scoped Quiz',
            'description' => null,
            'passing_score' => 70,
            'time_reward_minutes' => 15,
            'devices' => [(string) $child->id],
            'questions' => [
                [
                    'question' => 'Q?',
                    'type' => 'true_false',
                    'options' => ['True', 'False'],
                    'correct_answer' => 'True',
                ],
            ],
        ];

        $this->actingAs($user)->post(route('quizzes.store'), $payload)->assertRedirect(route('quizzes.index'));

        $quiz = Quiz::where('title', 'Scoped Quiz')->first();
        $this->assertNotNull($quiz);
        $this->assertSame([$child->id], $quiz->devices()->pluck('devices.id')->sort()->values()->all());
    }

    public function test_quiz_store_rejects_parent_role_device_assignment(): void
    {
        $user = User::factory()->create();
        $parentDevice = Device::factory()->create(['user_id' => $user->id, 'role' => 'parent']);

        $payload = [
            'title' => 'Bad Assign',
            'description' => null,
            'passing_score' => 70,
            'time_reward_minutes' => 15,
            'devices' => [(string) $parentDevice->id],
            'questions' => [
                [
                    'question' => 'Q?',
                    'type' => 'true_false',
                    'options' => ['True', 'False'],
                    'correct_answer' => 'True',
                ],
            ],
        ];

        $this->actingAs($user)->post(route('quizzes.store'), $payload)->assertSessionHasErrors(['devices.0']);
    }
}
