<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Video;
use App\Support\QuizSchoolLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalChildLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_known_child_first_paint_is_chooser_not_full_quiz_grid(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
        ]);
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Visible Quiz',
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

        $response = $this->get(route('portal.landing', ['mac' => $device->mac_address]));

        $response->assertOk();
        $response->assertDontSee('Available Quizzes', false);
        $response->assertSee('Earn time', false);
        $response->assertSee('portal-type-pair', false);
    }

    public function test_two_portal_taps_reach_start_on_recommended_quiz(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
        ]);
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Reco Quiz',
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

        $r1 = $this->get(route('portal.landing', ['mac' => $device->mac_address]));
        $r1->assertOk();
        $r1->assertSee('flow=quiz', false);

        $r2 = $this->get(route('portal.landing', ['mac' => $device->mac_address, 'flow' => 'quiz']));
        $r2->assertOk();
        $r2->assertSee('Start', false);
        $r2->assertSee(route('portal.quiz.show', ['mac' => $device->mac_address, 'quiz' => $quiz->id]), false);
    }

    public function test_phase_b_recommends_newer_assignment_first(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
        ]);

        $older = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Older Assign',
            'description' => null,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_count' => 10,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 10,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);
        $newer = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Newer Assign',
            'description' => null,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_count' => 10,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 10,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);

        $device->quizzes()->sync([$older->id]);
        DB::table('device_quiz')->where('device_id', $device->id)->where('quiz_id', $older->id)->update([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        $device->quizzes()->attach($newer->id, [
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $this->get(route('portal.landing', ['mac' => $device->mac_address, 'flow' => 'quiz']))
            ->assertOk()
            ->assertSee('Newer Assign', false)
            ->assertDontSee('Older Assign', false);
    }

    public function test_portal_shows_assigned_quizzes_for_child_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
        ]);
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Visible Without Age',
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

        $this->get(route('portal.landing', ['mac' => $device->mac_address]))
            ->assertOk()
            ->assertSee('flow=quiz', false);

        $this->get(route('portal.landing', ['mac' => $device->mac_address, 'flow' => 'quiz']))
            ->assertOk()
            ->assertSee('Visible Without Age', false);
    }

    public function test_invalid_preferred_quiz_falls_back_to_phase_b(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
        ]);
        $notAssigned = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Not On Device',
            'description' => null,
            'level' => 'Elementary',
            'subject' => 'Science',
            'question_count' => 5,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 10,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);
        $device->update(['preferred_quiz_id' => $notAssigned->id]);

        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Fallback Quiz',
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

        $this->get(route('portal.landing', ['mac' => $device->mac_address, 'flow' => 'quiz']))
            ->assertOk()
            ->assertSee('Fallback Quiz', false);
    }

    public function test_quiz_more_lists_custom_subject_heading_without_surprise_mix_chip(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
        ]);

        QuestionBankItem::create([
            'user_id' => null,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_text' => 'Bank Q',
            'option_a' => '1',
            'option_b' => '2',
            'option_c' => '3',
            'option_d' => '4',
            'correct_option' => 'A',
            'status' => 'Active',
        ]);

        $randomQuiz = Quiz::create([
            'user_id' => $user->id,
            'title' => Quiz::RANDOM_MODE_SETTINGS_TITLE,
            'description' => null,
            'level' => null,
            'subject' => null,
            'question_count' => 5,
            'scoring_mode' => 'time_reward',
            'minutes_per_correct' => 2,
            'passing_score' => 0,
            'time_reward_minutes' => 1,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);
        $randomQuiz->devices()->sync([
            $device->id => ['random_bank_levels' => ['Elementary']],
        ]);

        $other = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Filipino Custom',
            'description' => null,
            'level' => 'Elementary',
            'subject' => 'Filipino',
            'question_count' => 5,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 10,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);
        $other->devices()->sync([$device->id]);

        $this->get(route('portal.landing', ['mac' => $device->mac_address, 'flow' => 'quiz_more']))
            ->assertOk()
            ->assertDontSee('Surprise mix', false)
            ->assertSee('Filipino', false)
            ->assertSee('Filipino Custom', false);
    }

    public function test_any_school_level_quiz_lists_when_assigned(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
        ]);
        $highSchool = Quiz::create([
            'user_id' => $user->id,
            'title' => 'High School Math',
            'description' => null,
            'level' => 'High School',
            'subject' => 'Math',
            'question_count' => 5,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 10,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);
        $highSchool->devices()->sync([$device->id]);

        $this->get(route('portal.landing', ['mac' => $device->mac_address, 'flow' => 'quiz']))
            ->assertOk()
            ->assertSee('High School Math', false);
    }

    public function test_random_quiz_button_shows_when_question_bank_is_quiz_scoped(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
        ]);

        $kindergartenQuiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'K Math Pack',
            'description' => null,
            'level' => QuizSchoolLevel::KINDERGARTEN,
            'subject' => 'Math',
            'question_count' => 5,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 10,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);
        $kindergartenQuiz->devices()->sync([$device->id]);

        QuestionBankItem::create([
            'user_id' => $user->id,
            'quiz_id' => $kindergartenQuiz->id,
            'level' => QuizSchoolLevel::KINDERGARTEN,
            'subject' => 'Math',
            'question_text' => 'What is 1+1?',
            'option_a' => '1',
            'option_b' => '2',
            'option_c' => '3',
            'option_d' => '4',
            'correct_option' => 'B',
            'status' => 'Active',
        ]);

        $randomQuiz = Quiz::create([
            'user_id' => $user->id,
            'title' => Quiz::RANDOM_MODE_SETTINGS_TITLE,
            'description' => null,
            'level' => null,
            'subject' => null,
            'question_count' => 5,
            'scoring_mode' => 'time_reward',
            'minutes_per_correct' => 1,
            'passing_score' => 0,
            'time_reward_minutes' => 1,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);
        $randomQuiz->devices()->sync([
            $device->id => ['random_bank_levels' => [QuizSchoolLevel::KINDERGARTEN]],
        ]);

        $this->get(route('portal.landing', ['mac' => $device->mac_address, 'flow' => 'quiz']))
            ->assertOk()
            ->assertSee('Random quiz', false)
            ->assertSee('PARENTAL_WIFI_LOGO.png', false);
    }

    public function test_two_taps_to_video_start(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
        ]);
        $video = Video::create([
            'user_id' => $user->id,
            'title' => 'Learning Clip',
            'description' => null,
            'video_path' => 'videos/sample.mp4',
            'duration_seconds' => 120,
            'dictionary_words_enabled' => false,
            'word_count' => 0,
            'time_reward_minutes' => 10,
            'is_active' => true,
        ]);
        $video->devices()->sync([$device->id]);

        $this->get(route('portal.landing', ['mac' => $device->mac_address]))->assertOk();
        $this->get(route('portal.landing', ['mac' => $device->mac_address, 'flow' => 'video']))
            ->assertOk()
            ->assertSee('Start', false)
            ->assertSee(route('portal.video.show', ['mac' => $device->mac_address, 'video' => $video->id]), false);
    }
}
