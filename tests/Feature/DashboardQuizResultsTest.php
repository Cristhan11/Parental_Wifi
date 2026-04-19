<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardQuizResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_quiz_results_show_correct_over_total_not_percentage_over_one(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id, 'name' => 'Happy']);

        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Math 101',
            'description' => null,
            'passing_score' => 50,
            'time_reward_minutes' => 15,
            'questions' => [
                'questions' => [
                    ['id' => 1, 'question' => 'Q1', 'type' => 'true_false', 'options' => ['True', 'False'], 'correct_answer' => 'True'],
                    ['id' => 2, 'question' => 'Q2', 'type' => 'true_false', 'options' => ['True', 'False'], 'correct_answer' => 'True'],
                    ['id' => 3, 'question' => 'Q3', 'type' => 'true_false', 'options' => ['True', 'False'], 'correct_answer' => 'True'],
                ],
            ],
            'is_active' => true,
        ]);

        QuizAttempt::create([
            'device_id' => $device->id,
            'quiz_id' => $quiz->id,
            'answers' => ['answers' => []],
            'score' => 100,
            'passed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('3/3', false);
        $response->assertDontSee('100/1', false);
    }
}
