<?php

/**
 * VideoController - Parent Dashboard Video Management
 * 
 * This controller handles all video-related operations for parents in the dashboard.
 * Parents use this to create, edit, delete, and assign educational videos that their
 * children will watch to earn internet time.
 * 
 * How it works:
 * - Parents log in and access /videos
 * - They can upload video files (MP4, WebM, OGG)
 * - Videos are stored in storage/app/videos/
 * - Parents can enable dictionary words and set word count
 * - Parents can assign videos to specific devices
 * 
 * Security: Only authenticated parents can access these routes, and they can only
 * manage videos they created (checked via user_id).
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreVideoRequest;
use App\Http\Requests\UpdateVideoRequest;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class VideoController extends Controller
{
    /**
     * Display a listing of videos for the authenticated parent.
     * 
     * Route: GET /videos
     * 
     * What it does:
     * 1. Gets the currently logged-in parent user
     * 2. Fetches all videos created by this parent
     * 3. Counts how many times each video has been completed (for statistics)
     * 4. Orders them by newest first (latest())
     * 5. Displays them in a table view
     * 
     * Why withCount('completions')? This efficiently counts video completions without
     * loading all completion records, which is faster for large datasets.
     * 
     * @return View The videos index page showing all videos in a table
     */
    public function index(): View
    {
        // Get all videos for the logged-in parent
        // Auth::user() gets the currently authenticated user
        // ->videos() gets all videos this parent created (via relationship)
        $videos = Auth::user()->videos()
            ->withCount('completions')  // Count completions for each video (e.g., "5 completions")
            ->latest()                    // Order by newest first (created_at DESC)
            ->get();                      // Execute query and get results

        // Return the view with videos data
        // compact('videos') creates ['videos' => $videos] array
        return view('videos.index', compact('videos'));
    }

    /**
     * Show the form for creating a new video.
     * 
     * Route: GET /videos/create
     * 
     * What it does:
     * - Displays an empty form where parents can create a new video
     * - Form includes fields for: title, description, video file, duration,
     *   dictionary words settings, time reward, device assignment
     * - Gets child-role devices only (portal content is not assigned to parent/guest devices)
     * 
     * @return View The video creation form
     */
    public function create(): View
    {
        $devices = $this->videoAssignableDevices();

        return view('videos.create', compact('devices'));
    }

    /**
     * Store a newly created video in storage.
     * 
     * Route: POST /videos
     * 
     * What it does:
     * 1. Validates the form data (via StoreVideoRequest)
     * 2. Uploads video file to storage/app/videos/
     * 3. Generates unique filename to prevent conflicts
     * 4. Stores video record in database
     * 5. Assigns video to selected devices (many-to-many relationship)
     * 6. Redirects back to video list with success message
     * 
     * File Upload Process:
     * - Video file is validated (type, size) by StoreVideoRequest
     * - File is stored in storage/app/videos/ directory
     * - Filename is made unique using timestamp and original name
     * - Video path is stored in database (relative path: "videos/filename.mp4")
     * 
     * Device Assignment:
     * - If devices are selected, creates many-to-many relationship
     * - Uses pivot table 'device_video' to link devices and videos
     * 
     * @param StoreVideoRequest $request Validated form data (title, video file, etc.)
     * @return RedirectResponse Redirects to video list with success message
     */
    public function store(StoreVideoRequest $request): RedirectResponse
    {
        // Get validated form data (StoreVideoRequest ensures all required fields exist)
        $validated = $request->validated();

        // Handle video file upload
        // $request->file('video_file') gets the uploaded file
        // ->store('videos', 'public') stores it in storage/app/public/videos/ directory
        // Returns relative path: "videos/unique_filename.mp4"
        // Using 'public' disk so files are accessible via /storage/ symlink
        $videoPath = $request->file('video_file')->store('videos', 'public');

        // Handle checkbox fields: if checkbox is unchecked, it's not sent in request
        // So we need to check if the field exists in the request, not just in validated data
        $dictionaryWordsEnabled = $request->has('dictionary_words_enabled') ? (bool)$request->input('dictionary_words_enabled') : false;
        $isActive = $request->has('is_active') ? (bool)$request->input('is_active') : true;  // New videos are active by default
        
        // Create video record in database
        // Video::create() automatically saves to database
        $video = Video::create([
            'user_id' => Auth::id(),  // Link video to current parent (who created it)
            'title' => $validated['title'],  // Video name (e.g., "Educational Video 1")
            'description' => $validated['description'] ?? null,  // Optional description
            'video_path' => $videoPath,  // Relative path to video file (e.g., "videos/video_123.mp4")
            'duration_seconds' => $validated['duration_seconds'],  // Video length in seconds
            'dictionary_words_enabled' => $dictionaryWordsEnabled,  // Enable words?
            'word_count' => $dictionaryWordsEnabled ? ($validated['word_count'] ?? 0) : 0,  // Word count (0 if disabled)
            'time_reward_minutes' => $validated['time_reward_minutes'],  // Minutes granted if completed
            'is_active' => $isActive,  // Properly handle checkbox state
        ]);

        // Assign to selected child devices only (ignore tampered/non-child IDs)
        $video->devices()->sync($this->sanitizedVideoDeviceIds($request));

        // Redirect to video list page with success message
        // ->with() stores a message in session that displays on next page
        return redirect()->route('videos.index')
            ->with('success', 'Video created successfully!');
    }

    /**
     * Show the form for editing the specified video.
     * 
     * Route: GET /videos/{video}/edit
     * 
     * What it does:
     * 1. Checks if parent owns this video (security check)
     * 2. Loads video data from database
     * 3. Gets child-role devices for assignment options
     * 4. Gets currently assigned devices for this video
     * 5. Displays edit form pre-filled with existing video data
     * 
     * Security: Prevents parents from editing other parents' videos.
     * If someone tries to edit a video they don't own, they get a 403 Forbidden error.
     * 
     * @param Video $video The video to edit (Laravel automatically finds it by ID from URL)
     * @return View The video edit form with existing data
     */
    public function edit(Video $video): View
    {
        // Security check: Ensure user owns this video
        // Prevents unauthorized access to other parents' videos
        // $video->user_id is the parent who created it
        // Auth::id() is the currently logged-in parent
        if ($video->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');  // 403 = Forbidden
        }

        $devices = $this->videoAssignableDevices();

        $assignedDeviceIds = $video->devices()
            ->where('devices.role', 'child')
            ->pluck('devices.id')
            ->toArray();

        // Return edit form with video, devices, and assigned device IDs
        return view('videos.edit', compact('video', 'devices', 'assignedDeviceIds'));
    }

    /**
     * Update the specified video in storage.
     * 
     * Route: PUT /videos/{video}
     * 
     * What it does:
     * 1. Validates the updated form data
     * 2. Checks parent owns the video (security)
     * 3. Handles optional video file replacement
     * 4. Updates video in database
     * 5. Updates device assignments
     * 6. Redirects with success message
     * 
     * Video File Replacement:
     * - If new video file is uploaded, replaces old file
     * - Deletes old video file from storage
     * - Updates video_path in database
     * - If no new file, keeps existing video file
     * 
     * Device Assignment:
     * - ->sync() updates many-to-many relationships
     * - Removes old assignments, adds new ones
     * - If devices array is empty, removes all assignments
     * 
     * @param UpdateVideoRequest $request Validated form data
     * @param Video $video The video to update (found by ID from URL)
     * @return RedirectResponse Redirects to video list with success message
     */
    public function update(UpdateVideoRequest $request, Video $video): RedirectResponse
    {
        // Security check: Ensure user owns this video
        // Same check as edit() method - prevents unauthorized updates
        if ($video->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Get validated form data
        $validated = $request->validated();

        // Handle optional video file replacement
        // If parent uploads a new video file, replace the old one
        if ($request->hasFile('video_file')) {
            // Delete old video file from storage (using 'public' disk)
            // Prevents storage from filling up with unused files
            if (Storage::disk('public')->exists($video->video_path)) {
                Storage::disk('public')->delete($video->video_path);
            }

            // Store new video file
            // Same process as store() method
            // Store new video file in public disk (accessible via /storage/ symlink)
            $videoPath = $request->file('video_file')->store('videos', 'public');
            $validated['video_path'] = $videoPath;
        }

        // Update video record in database
        // $video->update() saves changes to existing record
        
        // Handle checkbox fields: if checkbox is unchecked, it's not sent in request
        // So we need to check if the field exists in the request, not just in validated data
        $isActive = $request->has('is_active') ? (bool)$request->input('is_active') : false;
        $dictionaryWordsEnabled = $request->has('dictionary_words_enabled') ? (bool)$request->input('dictionary_words_enabled') : false;
        
        $video->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'video_path' => $validated['video_path'] ?? $video->video_path,  // Use new path or keep existing
            'duration_seconds' => $validated['duration_seconds'] ?? $video->duration_seconds,  // Use new duration or keep existing
            'dictionary_words_enabled' => $dictionaryWordsEnabled,
            'word_count' => $dictionaryWordsEnabled ? ($validated['word_count'] ?? 0) : 0,
            'time_reward_minutes' => $validated['time_reward_minutes'],
            'is_active' => $isActive,  // Properly handle unchecked checkbox
        ]);

        $video->devices()->sync($this->sanitizedVideoDeviceIds($request));

        return redirect()->route('videos.index')
            ->with('success', 'Video updated successfully!');
    }

    /**
     * Remove the specified video from storage.
     * 
     * Route: DELETE /videos/{video}
     * 
     * What it does:
     * 1. Checks parent owns the video (security)
     * 2. Checks if video has been completed by children
     * 3. If completions exist, prevents deletion (preserves history)
     * 4. If no completions, deletes video file from storage
     * 5. Deletes video record from database
     * 
     * Why prevent deletion if completions exist?
     * - Preserves video completion history for parents to review
     * - Maintains data integrity (completions reference the video)
     * - Parents can deactivate instead (set is_active = false)
     * 
     * File Cleanup:
     * - Deletes video file from storage/app/videos/
     * - Prevents storage from filling up with unused files
     * 
     * @param Video $video The video to delete
     * @return RedirectResponse Redirects to video list with message
     */
    public function destroy(Video $video): RedirectResponse
    {
        // Security check: Ensure user owns this video
        if ($video->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Check if video has completions
        $completionCount = $video->completions()->count();
        
        // If video has completions, check if user wants to force delete
        // Force delete will remove video AND all its completion history
        if ($completionCount > 0) {
            // Check if force delete is requested (via query parameter or form field)
            $forceDelete = $request->input('force', false) || $request->has('force_delete');
            
            if (!$forceDelete) {
                return redirect()->route('videos.index')
                    ->with('error', "Cannot delete video with {$completionCount} completion(s). Use 'Force Delete' to remove video and all completion history.");
            }
            
            // Log force deletion for audit purposes
            Log::info("Force deleting video ID {$video->id} with {$completionCount} completions. User: " . Auth::id());
            
            // Note: Completions will be automatically deleted due to cascade delete
            // in the migration (onDelete('cascade'))
        }

        // Delete video file from storage (using 'public' disk)
        // Prevents storage from filling up with unused files
        // IMPORTANT: Delete file BEFORE deleting database record
        // This way we can still access $video->video_path if needed
        $videoPath = $video->video_path;
        
        try {
            // Check if file exists using the public disk
            if (Storage::disk('public')->exists($videoPath)) {
                // Attempt to delete the file
                $deleted = Storage::disk('public')->delete($videoPath);
                
                if ($deleted) {
                    Log::info("Successfully deleted video file: {$videoPath}");
                } else {
                    // Delete failed - try alternative method
                    $fullPath = storage_path('app/public/' . $videoPath);
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                        Log::info("Deleted video file using unlink(): {$fullPath}");
                    } else {
                        Log::warning("Video file not found for deletion: {$videoPath} (full path: {$fullPath})");
                    }
                }
            } else {
                // File doesn't exist at expected path - check if it exists elsewhere
                $fullPath = storage_path('app/public/' . $videoPath);
                if (file_exists($fullPath)) {
                    // File exists but Storage couldn't find it - delete directly
                    @unlink($fullPath);
                    Log::info("Deleted video file using direct unlink(): {$fullPath}");
                } else {
                    Log::info("Video file not found for deletion: {$videoPath}");
                }
            }
        } catch (\Exception $e) {
            // Log error but don't prevent video deletion from database
            // This ensures database stays clean even if file deletion fails
            Log::error("Error deleting video file {$videoPath}: " . $e->getMessage());
        }

        // Delete video record from database
        // This permanently removes the video record
        // Device assignments are automatically removed (cascade delete)
        $video->delete();

        return redirect()->route('videos.index')
            ->with('success', 'Video deleted successfully!');
    }

    /**
     * Registered child devices for this parent (videos are not assigned to parent/guest roles).
     */
    protected function videoAssignableDevices(): Collection
    {
        return Auth::user()
            ->devices()
            ->where('role', 'child')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<int>
     */
    protected function sanitizedVideoDeviceIds(Request $request): array
    {
        $allowedIds = $this->videoAssignableDevices()->pluck('id')->all();
        $submitted = array_map('intval', (array) $request->input('devices', []));

        return array_values(array_intersect($allowedIds, $submitted));
    }
}

