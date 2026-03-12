<?php
 
namespace App\Services;

use App\Events\TimeGranted;
use App\Models\Device;
use App\Models\DeviceTimeGrant;
use App\Models\QuizAttempt;
use App\Models\VideoCompletion;
use App\Services\NetworkService;
use App\Services\NoDogSplashService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Time Granting Service
 * 
 * This service is responsible for granting additional internet time to devices
 * after children successfully complete educational activities (quizzes or videos).
 * It validates that activities were completed correctly, extracts time rewards,
 * grants time to devices, and automatically unblocks devices that were previously
 * blocked due to expired time.
 * 
 
 */
class TimeGrantingService
{
    /**
     * NetworkService instance for network-level device blocking/unblocking.
     * 
     * This service handles iptables/firewall rules to physically block or
     * unblock devices at the network level. It's used to unblock devices
     * after time is granted so they can actually access the internet.
     */
    protected NetworkService $networkService;

    /**
     * NoDogSplashService instance for portal redirect management.
     * 
     * This service handles NoDogSplash configuration to redirect devices
     * to the portal or allow them through. It's used to remove redirects
     * after time is granted so devices can access internet normally.
     */
    protected NoDogSplashService $noDogSplashService;

    /**
     * Constructor - inject dependencies.
     * 
     * Laravel's dependency injection automatically provides NetworkService
     * and NoDogSplashService instances when this service is created.
     * This allows us to use these services in our methods.
     * 
     * @param NetworkService $networkService Service for network-level blocking
     * @param NoDogSplashService $noDogSplashService Service for portal redirects
     */
    public function __construct(NetworkService $networkService, NoDogSplashService $noDogSplashService)
    {
        $this->networkService = $networkService;
        $this->noDogSplashService = $noDogSplashService;
    }

    /**
     * Grant time to a device based on a quiz attempt.
     * 
     * This method validates that a quiz was successfully passed (not just attempted)
  
     * Validation Steps:
     * 1. Checks quiz attempt belongs to the device (security)
     * 2. Validates quiz was PASSED (passed === true) - NOT just attempted
     * 3. Validates attempt is completed (completed_at exists)
     * 4. Gets time reward from quiz configuration (time_reward_minutes)
     * 5. Validates time reward > 0
     * 
     * Important:
     * - Time is ONLY granted if quiz was passed
     * - Attempting but failing does NOT grant time
     * - Time reward comes from quiz configuration (set by parent)
     * 
     * @param Device $device The device that should receive time
     * @param QuizAttempt $quizAttempt The quiz attempt to validate
     * @return DeviceTimeGrant|null The created time grant record, or null if validation failed
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $quizAttempt = QuizAttempt::find(10);
     * $service = new TimeGrantingService();
     * 
     * // Grant time if quiz was passed
     * $grant = $service->grantTimeFromQuiz($device, $quizAttempt);
     * 
     * if ($grant) {
     *     echo "Success! Device now has {$device->fresh()->remaining_time_minutes} minutes";
     * } else {
     *     echo "Time not granted - quiz not passed or validation failed";
     * }
     * ```
     */
    public function grantTimeFromQuiz(Device $device, QuizAttempt $quizAttempt): ?DeviceTimeGrant
    {
        // Security check: Ensure the quiz attempt belongs to the same device
        // This prevents one device from granting time to another device
        // Example: Device A's quiz attempt cannot grant time to Device B
        if ($quizAttempt->device_id !== $device->id) {
            Log::warning('Quiz attempt device mismatch. Time not granted.', [
                'device_id' => $device->id,
                'attempt_device_id' => $quizAttempt->device_id,
                'quiz_attempt_id' => $quizAttempt->id,
            ]);

            return null; // Return null to indicate failure
        }

        // CRITICAL: Child must have PASSED the quiz to earn time
        // passed === true means child scored above passing_score threshold
        // passed === false means child failed - NO time granted
        // This ensures children only get rewarded for successful completion
        if (!$quizAttempt->passed) {
            Log::info('Quiz attempt not passed. Time not granted.', [
                'device_id' => $device->id,
                'quiz_attempt_id' => $quizAttempt->id,
            ]);

            return null; // Quiz failed - no time reward
        }

        // The attempt should be fully completed (timestamp recorded)
        // completed_at is set when child submits quiz answers
        // If null, quiz might still be in progress or not submitted
        if (is_null($quizAttempt->completed_at)) {
            Log::warning('Quiz attempt missing completion timestamp. Time not granted.', [
                'device_id' => $device->id,
                'quiz_attempt_id' => $quizAttempt->id,
            ]);

            return null; // Quiz not completed - no time reward
        }

        // Get the quiz that was attempted
        // quiz is a relationship, so we can access it directly
        // This gives us access to the quiz's time_reward_minutes configuration
        $quiz = $quizAttempt->quiz;

        // Safety check: Quiz should exist (data integrity)
        if (!$quiz) {
            Log::error('Quiz missing for attempt. Time not granted.', [
                'device_id' => $device->id,
                'quiz_attempt_id' => $quizAttempt->id,
            ]);

            return null; // Data corruption - quiz missing
        }

        // Get time reward amount from quiz configuration
        // time_reward_minutes is set by parent when creating quiz
        // Example: Parent creates quiz with 15 minutes reward
        // (int) casts to integer to ensure we have a number
        $minutes = (int) $quiz->time_reward_minutes;

        // Validate time reward is configured (must be > 0)
        // If parent didn't set a reward, child doesn't get time
        if ($minutes <= 0) {
            Log::warning('Quiz has no time reward configured. Time not granted.', [
                'device_id' => $device->id,
                'quiz_id' => $quiz->id,
            ]);

            return null; // No reward configured - no time granted
        }

        // All validations passed! Grant time to device
        // grantTime() handles the actual time granting and device unblocking
        // source='quiz' tells us this time came from quiz completion
        // sourceId=$quizAttempt->id links to the specific quiz attempt (audit trail)
        return $this->grantTime($device, $minutes, 'quiz', $quizAttempt->id);
    }

    /**
     * Grant time to a device based on a validated video completion.
     * 
     * This method validates that a video was successfully completed with correct
     * dictionary word validation and grants the configured time reward to the device.
     * Time is ONLY granted if ALL dictionary words shown during video intervals
     * were correctly entered by the child.
     * 
     * Validation Steps:
     * 1. Checks video completion belongs to the device (security)
     * 2. Validates dictionary word validation PASSED (passed_validation === true)
     * 3. Extra safety: Ensures ALL displayed words were answered correctly
     *    (words_correct >= words_shown_count)
     * 4. Validates completion timestamp exists
     * 5. Gets time reward from video configuration (time_reward_minutes)
     * 6. Validates time reward > 0
     * 
     * Important:
     * - Time is ONLY granted if ALL dictionary words were correctly entered
     * - Partial credit (some words correct) does NOT grant time
     * - Child must watch entire video AND correctly enter all words
     * - Time reward comes from video configuration (set by parent)
     * 
     * Dictionary Word System:
     * - Words appear at random intervals during video playback
     * - Child must remember and enter all words at video end
     * - System validates entered words against displayed words
     * - Only 100% correct = time reward granted
     * 
     * @param Device $device The device that should receive time
     * @param VideoCompletion $videoCompletion The video completion to validate
     * @return DeviceTimeGrant|null The created time grant record, or null if validation failed
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $videoCompletion = VideoCompletion::find(5);
     * $service = new TimeGrantingService();
     * 
     * // Grant time if video validation passed
     * $grant = $service->grantTimeFromVideo($device, $videoCompletion);
     * 
     * if ($grant) {
     *     echo "Success! Device now has {$device->fresh()->remaining_time_minutes} minutes";
     * } else {
     *     echo "Time not granted - dictionary words incorrect or validation failed";
     * }
     * ```
     */
    public function grantTimeFromVideo(Device $device, VideoCompletion $videoCompletion): ?DeviceTimeGrant
    {
        // Security check: Ensure the video completion belongs to the same device
        // This prevents one device from granting time to another device
        // Example: Device A's video completion cannot grant time to Device B
        if ($videoCompletion->device_id !== $device->id) {
            Log::warning('Video completion device mismatch. Time not granted.', [
                'device_id' => $device->id,
                'video_completion_id' => $videoCompletion->id,
            ]);

            return null; // Return null to indicate failure
        }

        // CRITICAL: Child must have PASSED dictionary word validation
        // passed_validation === true means child correctly entered ALL words
        // passed_validation === false means child got words wrong - NO time granted
        // This ensures children only get rewarded for correctly learning vocabulary
        if (!$videoCompletion->passed_validation) {
            Log::info('Video completion did not pass dictionary validation. Time not granted.', [
                'device_id' => $device->id,
                'video_completion_id' => $videoCompletion->id,
            ]);

            return null; // Dictionary words incorrect - no time reward
        }

        // Extra safety check: Ensure ALL displayed words were answered correctly
        // This double-checks the validation (defensive programming)
        // words_shown_count = how many words were displayed during video
        // words_correct = how many words child entered correctly
        // If words_correct < words_shown_count, child missed some words
        // Example: 5 words shown, only 4 correct = no time granted
        if (
            $videoCompletion->words_shown_count !== null
            && $videoCompletion->words_correct !== null
            && $videoCompletion->words_correct < $videoCompletion->words_shown_count
        ) {
            Log::info('Not all dictionary words were entered correctly. Time not granted.', [
                'device_id' => $device->id,
                'video_completion_id' => $videoCompletion->id,
                'words_shown' => $videoCompletion->words_shown_count,
                'words_correct' => $videoCompletion->words_correct,
            ]);

            return null; // Partial credit not allowed - all words must be correct
        }

        // The completion should have a timestamp (video was fully watched)
        // completed_at is set when video reaches the end
        // If null, video might still be playing or not finished
        if (is_null($videoCompletion->completed_at)) {
            Log::warning('Video completion missing completion timestamp. Time not granted.', [
                'device_id' => $device->id,
                'video_completion_id' => $videoCompletion->id,
            ]);

            return null; // Video not completed - no time reward
        }

        // Get the video that was completed
        // video is a relationship, so we can access it directly
        // This gives us access to the video's time_reward_minutes configuration
        $video = $videoCompletion->video;

        // Safety check: Video should exist (data integrity)
        if (!$video) {
            Log::error('Video missing for completion. Time not granted.', [
                'device_id' => $device->id,
                'video_completion_id' => $videoCompletion->id,
            ]);

            return null; // Data corruption - video missing
        }

        // Get time reward amount from video configuration
        // time_reward_minutes is set by parent when adding video
        // Example: Parent adds video with 30 minutes reward
        // (int) casts to integer to ensure we have a number
        $minutes = (int) $video->time_reward_minutes;

        // Validate time reward is configured (must be > 0)
        // If parent didn't set a reward, child doesn't get time
        if ($minutes <= 0) {
            Log::warning('Video has no time reward configured. Time not granted.', [
                'device_id' => $device->id,
                'video_id' => $video->id,
            ]);

            return null; // No reward configured - no time granted
        }

        // All validations passed! Grant time to device
        // grantTime() handles the actual time granting and device unblocking
        // source='video' tells us this time came from video completion
        // sourceId=$videoCompletion->id links to the specific video completion (audit trail)
        return $this->grantTime($device, $minutes, 'video', $videoCompletion->id);
    }

    /**
     * Generic method for granting time and performing follow-up actions.
     * 
     * This is the core method that actually grants time to a device. It handles:
     * 1. Validating the time amount
     * 2. Calling Device model to update database
     * 3. Refreshing device data
     * 4. Unblocking device if needed
     * 5. Logging the operation
     * 
     * This method is called by grantTimeFromQuiz() and grantTimeFromVideo()
     * after they validate the quiz/video completion. It can also be called
     * directly for manual time grants (e.g., parent grants bonus time).
     * 
     * What Happens:
     * - Device::grantTime() adds time to remaining_time_minutes
     * - Device::grantTime() updates total_time_allocated
     * - Device::grantTime() creates DeviceTimeGrant record (audit trail)
     * - Device is refreshed to get latest data
     * - If device was blocked, it's automatically unblocked
     * - Operation is logged for debugging and audit
     * 
     * @param Device $device The device receiving time
     * @param int $minutes Amount of time to grant (must be > 0)
     * @param string $source Source of grant: 'quiz', 'video', or 'manual'
     * @param int|null $sourceId Optional: ID of QuizAttempt or VideoCompletion (for audit trail)
     * @return DeviceTimeGrant The created time grant record
     * @throws InvalidArgumentException If minutes <= 0
     * 
     * Usage Example:
     * ```php
     * $service = new TimeGrantingService();
     * $device = Device::find(1);
     * 
     * // Grant 15 minutes from quiz
     * $grant = $service->grantTime($device, 15, 'quiz', $quizAttempt->id);
     * 
     * // Grant 30 minutes from video
     * $grant = $service->grantTime($device, 30, 'video', $videoCompletion->id);
     * 
     * // Grant 10 minutes manually (parent bonus)
     * $grant = $service->grantTime($device, 10, 'manual', null);
     * ```
     */
    public function grantTime(Device $device, int $minutes, string $source, ?int $sourceId = null): DeviceTimeGrant
    {
        // Validate minutes is positive (programming error if <= 0)
        // This throws an exception because it's a programming error, not a business logic failure
        // Business logic failures return null, programming errors throw exceptions
        if ($minutes <= 0) {
            throw new InvalidArgumentException('Granted minutes must be greater than zero.');
        }

        // Device::grantTime() handles all database operations:
        // - Adds $minutes to device's remaining_time_minutes
        // - Adds $minutes to device's total_time_allocated (tracking total ever granted)
        // - Creates DeviceTimeGrant record in database (audit trail)
        // - Returns the created DeviceTimeGrant record
        $grant = $device->grantTime($minutes, $source, $sourceId);

        // Refresh device from database to get latest data
        // This ensures we have the updated remaining_time_minutes and status
        // Important because grantTime() updated the database, but $device object might be stale
        $device->refresh();

        // Check if device should be unblocked after time grant
        // If device was blocked (time expired) and now has time, unblock it
        // shouldUnblockDevice() checks: status === 'blocked' AND remaining_time > 0
        if ($this->shouldUnblockDevice($device)) {
            // Unblock device so it can browse again
            // This updates device status from 'blocked' to 'active'
            $this->unblockDevice($device);
        }

        // Log the time grant operation for debugging and audit trail
        // This helps track when and why time was granted
        Log::info('Time granted to device.', [
            'device_id' => $device->id,
            'minutes_granted' => $minutes,
            'source' => $source, // 'quiz', 'video', or 'manual'
            'source_id' => $sourceId, // ID of quiz attempt or video completion (if applicable)
            'remaining_time_minutes' => $device->remaining_time_minutes, // Updated remaining time
            'device_status' => $device->status, // 'active' or 'blocked' (should be 'active' if unblocked)
        ]);

        if ($device->user_id) {
            // Emit realtime feedback to parent dashboard after successful grant.
            // Why here: this is the single source-of-truth point where remaining
            // time has already been updated and (if needed) unblock flow completed.
            event(new TimeGranted(
                userId: $device->user_id,
                deviceId: $device->id,
                deviceName: $device->name,
                minutesGranted: $minutes,
                remainingMinutes: (int) ($device->remaining_time_minutes ?? 0),
                source: $source
            ));
        }

        // Return the created DeviceTimeGrant record
        // This can be used by callers to track the grant (e.g., display to user)
        return $grant;
    }

    /**
     * Determine if the device should be unblocked after granting time.
     * 
     * This helper method checks if a device needs to be unblocked after receiving
     * a time grant. A device should be unblocked if:
     * 1. Device status is 'blocked' (was blocked due to expired time)
     * 2. Device now has remaining time > 0 (time was just granted)
     * 
     * Why This Matters:
     * - When time expires, device status becomes 'blocked' (no internet access)
     * - After time is granted, device should be unblocked so browsing can resume
     * - This method determines if unblocking is needed
     * 
     * @param Device $device The device to check
     * @return bool True if device should be unblocked, false otherwise
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $device->status = 'blocked'; // Time expired
     * $device->remaining_time_minutes = 15; // Time just granted
     * 
     * if ($this->shouldUnblockDevice($device)) {
     *     // Device should be unblocked (status='blocked' AND time > 0)
     *     $this->unblockDevice($device);
     * }
     * ```
     */
    protected function shouldUnblockDevice(Device $device): bool
    {
        // Get remaining time (default to 0 if null)
        // (int) casts to integer, ?? 0 provides default if null
        $remaining = (int) ($device->remaining_time_minutes ?? 0);

        // Device should be unblocked if:
        // - Status is 'blocked' (was blocked due to expired time)
        // - AND has remaining time > 0 (time was just granted)
        // If status is already 'active', no need to unblock
        // If remaining time is 0, device shouldn't be unblocked (no time available)
        return $device->status === 'blocked' && $remaining > 0;
    }

    /**
     * Unblock the device so it can browse again.
     * 
     * This method unblocks a device after time is granted. It performs a complete
     * unblocking at three levels:
     * 1. Database level: Updates device status from 'blocked' to 'active'
     * 2. Network level: Removes iptables/firewall rules that block the device
     * 3. Portal level: Removes NoDogSplash redirect so device can access internet
     * 
     * Why We Need All Three Levels:
     * - Database status: Tracks device state in our application (for UI, reports)
     * - Network blocking: Physically prevents device from accessing internet (iptables)
     * - Portal redirect: Intercepts HTTP requests and redirects to portal (NoDogSplash)
     * - All three must be in sync for device to actually access internet
     * 
     * Order of Operations:
     * 1. Update database status first (source of truth)
     * 2. Unblock at network level (remove iptables rules)
     * 3. Remove portal redirect (allow device through NoDogSplash)
     * 
     * This order ensures:
     * - Database is updated first (so other parts of system see correct status)
     * - Network blocking is removed (so device can physically access internet)
     * - Portal redirect is removed (so device isn't redirected to portal)
     * 
     * Error Handling:
     * - If network unblocking fails, we still update database and log error
     * - If portal redirect removal fails, we still update database and log error
     * - This ensures partial success - device status is updated even if network operations fail
     * - Errors are logged so we can debug and fix issues
     * 
     * When Is This Called?
     * - After child completes quiz and earns time (via grantTimeFromQuiz)
     * - After child completes video and earns time (via grantTimeFromVideo)
     * - When parent manually grants time to device
     * 
     * @param Device $device The device to unblock
     * @return void No return value
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $device->status = 'blocked'; // Currently blocked (time expired)
     * 
     * $this->unblockDevice($device);
     * // Device status is now 'active' (database)
     * // Device is unblocked at network level (iptables)
     * // Device redirect is removed (NoDogSplash)
     * // Device can browse internet again
     * ```
     */
    protected function unblockDevice(Device $device): void
    {
        // Step 1: Update device status from 'blocked' to 'active' in database
        // This is the "source of truth" for our application
        // Other parts of the system check database status to know if device is blocked
        // 
        // Why update database first?
        // - Database status is checked by other services and controllers
        // - Even if network operations fail, we record the intent
        // - Status change: 'blocked' → 'active'
        $device->update(['status' => 'active']);

        // Step 2: Unblock device at network level using NetworkService
        // This removes the iptables/firewall rules that were blocking the device
        // 
        // What NetworkService::unblockDevice() does:
        // - Gets device's MAC address
        // - Removes iptables rule that blocks that MAC address
        // - Device can now physically access internet (firewall allows it)
        // 
        // Current implementation (stub):
        // - Only updates database status (already done above)
        // - Logs the operation
        // - Actual iptables unblocking will be implemented in TODO #12
        // 
        // Error handling:
        // - If network unblocking fails, we catch and log the error
        // - We continue with portal redirect removal (partial success)
        try {
            $networkUnblocked = $this->networkService->unblockDevice($device);
            
            if (!$networkUnblocked) {
                // Log warning if network unblocking failed
                // This helps us debug network issues
                Log::warning('Network unblocking may have failed', [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'mac_address' => $device->mac_address,
                ]);
            }
        } catch (\Exception $e) {
            // If network unblocking throws an exception, catch it here
            // Log the error but continue with portal redirect removal
            // This ensures we still try to remove redirect even if network fails
            Log::error('Error unblocking device at network level', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $device->mac_address,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Step 3: Remove portal redirect using NoDogSplashService
        // This removes the NoDogSplash configuration that redirects device to portal
        // 
        // What NoDogSplashService::allowDeviceThrough() does:
        // - Gets device's MAC address
        // - Removes redirect rule from NoDogSplash config file
        // - Restarts NoDogSplash service to apply changes
        // - Device's HTTP requests now go to actual websites (not portal)
        // 
        // Current implementation (stub):
        // - Only logs the operation
        // - Actual NoDogSplash config removal will be implemented in TODO #15
        // 
        // Error handling:
        // - If redirect removal fails, we catch and log the error
        // - Database and network unblocking already succeeded (partial success)
        try {
            $redirectRemoved = $this->noDogSplashService->allowDeviceThrough($device);
            
            if (!$redirectRemoved) {
                // Log warning if redirect removal failed
                // This helps us debug NoDogSplash issues
                Log::warning('Portal redirect removal may have failed', [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'mac_address' => $device->mac_address,
                ]);
            }
        } catch (\Exception $e) {
            // If redirect removal throws an exception, catch it here
            // Log the error - database and network unblocking already succeeded
            // This ensures we don't crash even if redirect removal fails
            Log::error('Error removing portal redirect', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $device->mac_address,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Step 4: Log successful unblocking operation
        // This creates an audit trail of when devices were unblocked and why
        // Helps with debugging and monitoring system health
        // 
        // Note: We log even if network or redirect operations failed
        // This gives us a complete picture of what happened
        Log::info('Device unblocked after time grant (complete unblocking process)', [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'mac_address' => $device->mac_address,
            'remaining_time_minutes' => $device->remaining_time_minutes, // Time available after grant
            'status' => 'active', // Device status after unblocking
        ]);
    }
}


