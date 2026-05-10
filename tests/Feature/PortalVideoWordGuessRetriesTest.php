<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DictionaryWord;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoCompletion;
use App\Models\VideoWordDisplay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalVideoWordGuessRetriesTest extends TestCase
{
    use RefreshDatabase;

    private function seedVideoWithOneWord(Device $device): array
    {
        $user = User::factory()->create();
        $device->user_id = $user->id;
        $device->save();

        foreach (['w1', 'w2', 'w3', 'w4', 'w5', 'w6'] as $w) {
            DictionaryWord::create([
                'word' => $w,
                'definition' => 'def '.$w,
                'difficulty_level' => 'easy',
                'is_built_in' => false,
                'user_id' => null,
            ]);
        }

        $video = Video::create([
            'user_id' => $user->id,
            'title' => 'Test Vid',
            'description' => null,
            'video_path' => 'videos/test.mp4',
            'duration_seconds' => 120,
            'dictionary_words_enabled' => true,
            'word_count' => 1,
            'time_reward_minutes' => 5,
            'is_active' => true,
        ]);

        $device->videos()->syncWithoutDetaching([$video->id]);

        $dw = DictionaryWord::where('word', 'w1')->firstOrFail();

        $completion = VideoCompletion::create([
            'device_id' => $device->id,
            'video_id' => $video->id,
            'attempt_number' => 1,
            'completed_at' => null,
            'watched_duration' => 0,
            'words_shown_count' => 1,
            'words_entered' => null,
            'words_correct' => 0,
            'passed_validation' => false,
            'word_guess_failed_count' => 0,
        ]);

        VideoWordDisplay::create([
            'video_completion_id' => $completion->id,
            'dictionary_word_id' => $dw->id,
            'displayed_at_timestamp' => 10,
            'word_text' => 'w1',
        ]);

        return [$video, $completion];
    }

    public function test_first_two_wrong_submissions_redirect_to_word_retry_without_result(): void
    {
        $device = Device::factory()->create();
        [$video, $completion] = $this->seedVideoWithOneWord($device);

        $r1 = $this->withSession(['video_completion_id' => $completion->id])
            ->post(route('portal.video.submit'), [
                'mac' => $device->mac_address,
                'words' => ['nope'],
            ]);

        $r1->assertRedirect(route('portal.video.show', [
            'video' => $video->id,
            'mac' => $device->mac_address,
            'word_retry' => 1,
        ]));

        $completion->refresh();
        $this->assertSame(1, $completion->word_guess_failed_count);
        $this->assertNull($completion->completed_at);

        $r2 = $this->withSession(['video_completion_id' => $completion->id])
            ->post(route('portal.video.submit'), [
                'mac' => $device->mac_address,
                'words' => ['still-wrong'],
            ]);

        $r2->assertRedirect(route('portal.video.show', [
            'video' => $video->id,
            'mac' => $device->mac_address,
            'word_retry' => 1,
        ]));

        $completion->refresh();
        $this->assertSame(2, $completion->word_guess_failed_count);
        $this->assertNull($completion->completed_at);
    }

    public function test_third_wrong_submission_redirects_to_result_page(): void
    {
        $device = Device::factory()->create();
        [$video, $completion] = $this->seedVideoWithOneWord($device);

        $completion->update(['word_guess_failed_count' => 2]);

        $r = $this->withSession(['video_completion_id' => $completion->id])
            ->post(route('portal.video.submit'), [
                'mac' => $device->mac_address,
                'words' => ['wrong-again'],
            ]);

        $completion->refresh();
        $this->assertSame(3, $completion->word_guess_failed_count);
        $this->assertNotNull($completion->completed_at);

        $r->assertRedirect(route('portal.video.result', $completion));
    }

    public function test_word_retry_page_loads_when_session_valid(): void
    {
        $device = Device::factory()->create();
        [$video, $completion] = $this->seedVideoWithOneWord($device);
        $completion->update(['word_guess_failed_count' => 1]);

        $response = $this->withSession(['video_completion_id' => $completion->id])
            ->get(route('portal.video.show', [
                'video' => $video->id,
                'mac' => $device->mac_address,
                'word_retry' => 1,
            ]));

        $response->assertOk();
        $response->assertSee('Try the words again', false);
        $response->assertSee('portalWordGameModal', false);
    }
}
