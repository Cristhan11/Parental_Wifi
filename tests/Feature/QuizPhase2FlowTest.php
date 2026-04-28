<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceTimeGrant;
use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizPhase2FlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_quiz_draws_configured_count_from_question_bank(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id, 'role' => 'child']);
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Bank Quiz',
            'description' => null,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_count' => 5,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 10,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);
        $quiz->devices()->sync([$device->id]);

        for ($i = 1; $i <= 12; $i++) {
            QuestionBankItem::create([
                'user_id' => null,
                'level' => 'Elementary',
                'subject' => 'Math',
                'question_text' => "Question {$i}",
                'option_a' => 'A',
                'option_b' => 'B',
                'option_c' => 'C',
                'option_d' => 'D',
                'correct_option' => 'A',
                'status' => 'Active',
            ]);
        }

        $response = $this->get(route('portal.quiz.show', ['quiz' => $quiz, 'mac' => $device->mac_address]));

        $response->assertOk();
        $response->assertViewHas('questions', fn (array $questions) => count($questions) === 5);
    }

    public function test_time_reward_mode_grants_minutes_per_correct_answer(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id, 'role' => 'child', 'status' => 'active']);
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Reward Quiz',
            'description' => null,
            'level' => 'Elementary',
            'subject' => 'Science',
            'question_count' => 5,
            'scoring_mode' => 'time_reward',
            'minutes_per_correct' => 2,
            'passing_score' => 100,
            'time_reward_minutes' => 1,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);
        $quiz->devices()->sync([$device->id]);

        for ($i = 1; $i <= 5; $i++) {
            QuestionBankItem::create([
                'user_id' => null,
                'level' => 'Elementary',
                'subject' => 'Science',
                'question_text' => "Sci Question {$i}",
                'option_a' => 'Correct',
                'option_b' => 'Wrong 1',
                'option_c' => 'Wrong 2',
                'option_d' => 'Wrong 3',
                'correct_option' => 'A',
                'status' => 'Active',
            ]);
        }

        $this->get(route('portal.quiz.show', ['quiz' => $quiz, 'mac' => $device->mac_address]))->assertOk();

        $submit = $this->post(route('portal.quiz.submit', ['mac' => $device->mac_address]), [
            'mac' => $device->mac_address,
            'answers' => ['A', 'A', 'A', 'A', 'A'],
        ]);

        $submit->assertRedirect();
        $attempt = QuizAttempt::query()->latest('id')->first();

        $this->assertNotNull($attempt);
        $this->assertTrue((bool) $attempt->passed);
        $this->assertSame(5, (int) $attempt->correct_count);
        $this->assertDatabaseHas('device_time_grants', [
            'device_id' => $device->id,
            'source' => 'quiz',
            'source_id' => $attempt->id,
            'minutes_granted' => 10,
        ]);
        $this->assertGreaterThanOrEqual(1, DeviceTimeGrant::query()->count());
    }
}
