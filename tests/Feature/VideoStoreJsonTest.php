<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoStoreJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_returns_json_for_xhr_with_success_redirect_url_and_creates_video(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('lesson.mp4', 128, 'video/mp4');

        $response = $this->actingAs($user)->postJson(route('videos.store'), [
            'title' => 'Science intro',
            'description' => null,
            'video_file' => $file,
            'duration_seconds' => 120,
            'time_reward_minutes' => 10,
            'is_active' => '1',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Video created successfully!',
            ])
            ->assertJsonPath('redirect_url', route('videos.index'));

        $this->assertDatabaseHas('videos', [
            'user_id' => $user->id,
            'title' => 'Science intro',
            'duration_seconds' => 120,
            'time_reward_minutes' => 10,
        ]);

        $video = Video::where('title', 'Science intro')->first();
        $this->assertNotNull($video);
        Storage::disk('public')->assertExists($video->video_path);
    }

    public function test_store_returns_validation_errors_as_json_for_xhr(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('videos.store'), [
            'title' => '',
            'video_file' => UploadedFile::fake()->create('x.mp4', 10, 'video/mp4'),
            'duration_seconds' => 1,
            'time_reward_minutes' => 5,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }
}
