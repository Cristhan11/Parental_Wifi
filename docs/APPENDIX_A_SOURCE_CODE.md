APPENDIX A: COMPLETE AND DETAILED SOURCE CODE

This appendix shows the source code for the Child-Centric WiFi Monitoring and Control System. Selected code demonstrates how the system works: time tracking, device management, portal operations, network control, and background job processing. Comments were removed to keep the code concise but readable.

A.1	Time Tracking Service

TimeTrackingService provides the foundation for the captive portal. It monitors device internet time, calculates remaining time, detects when time expires, and tracks active sessions.

<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class TimeTrackingService
{
    public function calculateRemainingTime(Device $device): int
    {
        if ($device->isWhitelisted()) {
            return 999999;
        }

        $baseRemaining = $device->remaining_time_minutes ?? 0;
        $activeSession = $device->activeSession();

        if (!$activeSession) {
            return max(0, $baseRemaining);
        }

        $sessionDurationMinutes = $activeSession->getDurationMinutes();
        $accurateRemaining = $baseRemaining - $sessionDurationMinutes;

        return max(0, (int) floor($accurateRemaining));
    }

    public function hasTimeExpired(Device $device): bool
    {
        if ($device->isWhitelisted()) {
            return false;
        }

        $remaining = $this->calculateRemainingTime($device);
        return $remaining <= 0;
    }

    public function getExpiredDevices(): Collection
    {
        $activeDevices = Device::where('status', 'active')->get();

        $expiredDevices = $activeDevices->filter(function (Device $device) {
            return $this->hasTimeExpired($device);
        });

        return $expiredDevices;
    }

    public function startSession(Device $device): ?DeviceSession
    {
        $isApproved = $device->status === 'active' || $device->status === 'whitelisted';

        if (!$isApproved) {
            Log::warning("Unauthorized device attempted to start session", [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $device->mac_address,
                'status' => $device->status,
                'ip_address' => $device->ip_address,
                'timestamp' => now(),
            ]);

            return null;
        }

        $existingSession = $device->activeSession();

        if ($existingSession) {
            return $existingSession;
        }

        $session = $device->sessions()->create([
            'started_at' => now(),
            'ended_at' => null,
            'duration_seconds' => null,
            'total_bytes_sent' => 0,
            'total_bytes_received' => 0,
        ]);

        Log::info("Session started for device {$device->name} (ID: {$device->id})", [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'session_id' => $session->id,
            'mac_address' => $device->mac_address,
            'status' => $device->status,
            'started_at' => $session->started_at,
        ]);

        return $session;
    }

    public function trackActiveSessions(): void
    {
        $activeSessions = DeviceSession::whereNull('ended_at')
            ->with('device')
            ->get();

        if ($activeSessions->isEmpty()) {
            return;
        }

        foreach ($activeSessions as $session) {
            $device = $session->device;

            if ($device->isWhitelisted()) {
                continue;
            }

            $sessionDurationMinutes = $session->getDurationMinutes();

            if ($sessionDurationMinutes >= 1) {
                $minutesToDeduct = (int) ceil($sessionDurationMinutes);
                $device->deductTime($minutesToDeduct);
                $device->update(['last_seen_at' => now()]);

                Log::debug("Time deducted from active session", [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'session_id' => $session->id,
                    'session_duration_minutes' => $sessionDurationMinutes,
                    'minutes_deducted' => $minutesToDeduct,
                    'remaining_time_minutes' => $device->fresh()->remaining_time_minutes,
                ]);
            }
        }
    }
}

A.2	Time Granting Service

TimeGrantingService gives devices more internet time when children finish educational activities successfully.

<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceTimeGrant;
use App\Models\QuizAttempt;
use App\Models\VideoCompletion;
use App\Services\NetworkService;
use App\Services\NoDogSplashService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class TimeGrantingService
{
    protected NetworkService $networkService;
    protected NoDogSplashService $noDogSplashService;

    public function __construct(NetworkService $networkService, NoDogSplashService $noDogSplashService)
    {
        $this->networkService = $networkService;
        $this->noDogSplashService = $noDogSplashService;
    }

    public function grantTimeFromQuiz(Device $device, QuizAttempt $quizAttempt): ?DeviceTimeGrant
    {
        if ($quizAttempt->device_id !== $device->id) {
            Log::warning('Quiz attempt device mismatch. Time not granted.', [
                'device_id' => $device->id,
                'attempt_device_id' => $quizAttempt->device_id,
                'quiz_attempt_id' => $quizAttempt->id,
            ]);

            return null;
        }

        if (!$quizAttempt->passed) {
            Log::info('Quiz attempt not passed. Time not granted.', [
                'device_id' => $device->id,
                'quiz_attempt_id' => $quizAttempt->id,
            ]);

            return null;
        }

        if (is_null($quizAttempt->completed_at)) {
            Log::warning('Quiz attempt missing completion timestamp. Time not granted.', [
                'device_id' => $device->id,
                'quiz_attempt_id' => $quizAttempt->id,
            ]);

            return null;
        }

        $quiz = $quizAttempt->quiz;

        if (!$quiz) {
            Log::error('Quiz missing for attempt. Time not granted.', [
                'device_id' => $device->id,
                'quiz_attempt_id' => $quizAttempt->id,
            ]);

            return null;
        }

        $minutes = (int) $quiz->time_reward_minutes;

        if ($minutes <= 0) {
            Log::warning('Quiz has no time reward configured. Time not granted.', [
                'device_id' => $device->id,
                'quiz_id' => $quiz->id,
            ]);

            return null;
        }

        return $this->grantTime($device, $minutes, 'quiz', $quizAttempt->id);
    }

    public function grantTimeFromVideo(Device $device, VideoCompletion $videoCompletion): ?DeviceTimeGrant
    {
        if ($videoCompletion->device_id !== $device->id) {
            Log::warning('Video completion device mismatch. Time not granted.', [
                'device_id' => $device->id,
                'video_completion_id' => $videoCompletion->id,
            ]);

            return null;
        }

        if (!$videoCompletion->passed_validation) {
            Log::info('Video completion did not pass dictionary validation. Time not granted.', [
                'device_id' => $device->id,
                'video_completion_id' => $videoCompletion->id,
            ]);

            return null;
        }

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

            return null;
        }

        if (is_null($videoCompletion->completed_at)) {
            Log::warning('Video completion missing completion timestamp. Time not granted.', [
                'device_id' => $device->id,
                'video_completion_id' => $videoCompletion->id,
            ]);

            return null;
        }

        $video = $videoCompletion->video;

        if (!$video) {
            Log::error('Video missing for completion. Time not granted.', [
                'device_id' => $device->id,
                'video_completion_id' => $videoCompletion->id,
            ]);

            return null;
        }

        $minutes = (int) $video->time_reward_minutes;

        if ($minutes <= 0) {
            Log::warning('Video has no time reward configured. Time not granted.', [
                'device_id' => $device->id,
                'video_id' => $video->id,
            ]);

            return null;
        }

        return $this->grantTime($device, $minutes, 'video', $videoCompletion->id);
    }

    public function grantTime(Device $device, int $minutes, string $source, ?int $sourceId = null): DeviceTimeGrant
    {
        if ($minutes <= 0) {
            throw new InvalidArgumentException('Granted minutes must be greater than zero.');
        }

        $grant = $device->grantTime($minutes, $source, $sourceId);
        $device->refresh();

        if ($this->shouldUnblockDevice($device)) {
            $this->unblockDevice($device);
        }

        Log::info('Time granted to device.', [
            'device_id' => $device->id,
            'minutes_granted' => $minutes,
            'source' => $source,
            'source_id' => $sourceId,
            'remaining_time_minutes' => $device->remaining_time_minutes,
            'device_status' => $device->status,
        ]);

        return $grant;
    }

    protected function shouldUnblockDevice(Device $device): bool
    {
        $remaining = (int) ($device->remaining_time_minutes ?? 0);
        return $device->status === 'blocked' && $remaining > 0;
    }

    protected function unblockDevice(Device $device): void
    {
        $device->update(['status' => 'active']);

        try {
            $networkUnblocked = $this->networkService->unblockDevice($device);
            
            if (!$networkUnblocked) {
                Log::warning('Network unblocking may have failed', [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'mac_address' => $device->mac_address,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error unblocking device at network level', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $device->mac_address,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        try {
            $redirectRemoved = $this->noDogSplashService->allowDeviceThrough($device);
            
            if (!$redirectRemoved) {
                Log::warning('Portal redirect removal may have failed', [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'mac_address' => $device->mac_address,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error removing portal redirect', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $device->mac_address,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        Log::info('Device unblocked after time grant (complete unblocking process)', [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'mac_address' => $device->mac_address,
            'remaining_time_minutes' => $device->remaining_time_minutes,
            'status' => 'active',
        ]);
    }
}

A.3	Network Service

NetworkService blocks and unblocks devices at the network level using firewall rules.

<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Log;

class NetworkService
{
    protected ScriptExecutor $scriptExecutor;

    public function __construct(ScriptExecutor $scriptExecutor)
    {
        $this->scriptExecutor = $scriptExecutor;
    }

    public function blockDevice(Device $device): bool
    {
        $macAddress = $device->mac_address;

        if (empty($macAddress)) {
            Log::error('Cannot block device: MAC address is missing', [
                'device_id' => $device->id,
                'device_name' => $device->name,
            ]);

            return false;
        }

        $result = $this->scriptExecutor->execute('block_device.sh', [$macAddress]);
        $scriptSuccess = $result['success'];
        $device->update(['status' => 'blocked']);

        if ($scriptSuccess) {
            Log::info('Device blocked at network level', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'status' => 'blocked',
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
            ]);
        } else {
            Log::warning('Device blocking: database updated but network blocking may have failed', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'status' => 'blocked',
                'script_error' => $result['error'],
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
                'command' => $result['command'],
            ]);
        }

        return $scriptSuccess;
    }

    public function unblockDevice(Device $device): bool
    {
        $macAddress = $device->mac_address;

        if (empty($macAddress)) {
            Log::error('Cannot unblock device: MAC address is missing', [
                'device_id' => $device->id,
                'device_name' => $device->name,
            ]);

            return false;
        }

        $result = $this->scriptExecutor->execute('unblock_device.sh', [$macAddress]);
        $scriptSuccess = $result['success'];
        $device->update(['status' => 'active']);

        if ($scriptSuccess) {
            Log::info('Device unblocked at network level', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'status' => 'active',
                'remaining_time_minutes' => $device->remaining_time_minutes,
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
            ]);
        } else {
            Log::warning('Device unblocking: database updated but network unblocking may have failed', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'status' => 'active',
                'remaining_time_minutes' => $device->remaining_time_minutes,
                'script_error' => $result['error'],
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
                'command' => $result['command'],
            ]);
        }

        return $scriptSuccess;
    }

    public function getConnectedDevices(): array
    {
        $result = $this->scriptExecutor->execute('get_connected_devices.sh', []);

        if (!$result['success']) {
            Log::warning('Failed to get connected devices', [
                'script_error' => $result['error'],
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
            ]);

            return [];
        }

        $output = trim($result['output']);

        if (empty($output)) {
            Log::debug('No connected devices found', [
                'script_output' => $output,
            ]);

            return [];
        }

        $devices = json_decode($output, true);

        if ($devices === null || !is_array($devices)) {
            Log::error('Failed to parse connected devices JSON', [
                'script_output' => $output,
                'json_error' => json_last_error_msg(),
            ]);

            return [];
        }

        $validDevices = [];
        foreach ($devices as $device) {
            if (isset($device['mac']) && isset($device['ip'])) {
                $validDevices[] = [
                    'mac_address' => $device['mac'],
                    'ip_address' => $device['ip'],
                    'hostname' => $device['hostname'] ?? '',
                ];
            }
        }

        if (!empty($validDevices)) {
            Log::debug('Retrieved connected devices', [
                'device_count' => count($validDevices),
            ]);
        }

        return $validDevices;
    }
}

A.4	Portal Controller

PortalController handles the captive portal interface. It lets children access quizzes and videos that earn them internet time.

<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Video;
use App\Models\VideoCompletion;
use App\Models\VideoWordDisplay;
use App\Services\TimeGrantingService;
use App\Services\VideoWordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PortalController extends Controller
{
    protected TimeGrantingService $timeGrantingService;
    protected VideoWordService $videoWordService;

    public function __construct(TimeGrantingService $timeGrantingService, VideoWordService $videoWordService)
    {
        $this->timeGrantingService = $timeGrantingService;
        $this->videoWordService = $videoWordService;
    }

    public function landing(Request $request): View
    {
        $device = $this->getDevice($request);

        if (!$device) {
            return view('portal.landing', [
                'device' => null,
                'error' => 'Device not found. Please connect to the network.',
            ]);
        }

        $quizzes = $device->quizzes()
            ->where('is_active', true)
            ->get();

        $videos = $device->videos()
            ->where('is_active', true)
            ->get();

        return view('portal.landing', [
            'device' => $device,
            'quizzes' => $quizzes,
            'videos' => $videos,
        ]);
    }

    protected function getDevice(Request $request): ?Device
    {
        $token = $request->query('tok');
        if ($token) {
            $macAddress = $this->getMacFromToken($token);
            if ($macAddress) {
                session(['device_mac' => $macAddress]);
                $device = Device::where('mac_address', $macAddress)->first();
                if ($device) {
                    return $device;
                }
            }
        }

        $macAddress = $request->query('mac')
            ?? $request->input('mac')
            ?? session('device_mac');

        if (!$macAddress) {
            return null;
        }

        return Device::where('mac_address', $macAddress)->first();
    }

    public function submitQuiz(Request $request): RedirectResponse
    {
        $device = $this->getDevice($request);
        $quizAttemptData = session('quiz_attempt');

        if (!$device || !$quizAttemptData) {
            return redirect()->route('portal.landing')
                ->with('error', 'Session expired. Please try again.');
        }

        $quiz = Quiz::findOrFail($quizAttemptData['quiz_id']);
        $questions = $quizAttemptData['questions'];
        $submittedAnswers = $request->input('answers', []);

        $correctCount = 0;
        $totalQuestions = count($questions);

        foreach ($questions as $index => $question) {
            $submittedAnswer = $submittedAnswers[$index] ?? null;
            $correctAnswer = $question['correct_answer'];

            $isCorrect = false;

            if ($question['type'] === 'multiple_choice') {
                $submittedLetter = strtolower(trim($submittedAnswer));
                $options = $question['options'] ?? [];
                $submittedIndex = ord($submittedLetter) - ord('a');

                if (isset($options[$submittedIndex])) {
                    $submittedOptionValue = strtolower(trim($options[$submittedIndex]));
                    $correctAnswerValue = strtolower(trim($correctAnswer));
                    $isCorrect = $submittedOptionValue === $correctAnswerValue;
                }
            } elseif ($question['type'] === 'true_false') {
                $isCorrect = strtolower(trim($submittedAnswer)) === strtolower(trim($correctAnswer));
            } else {
                $isCorrect = strtolower(trim($submittedAnswer)) === strtolower(trim($correctAnswer));
            }

            if ($isCorrect) {
                $correctCount++;
            }
        }

        $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;
        $passed = $quiz->isPassingScore($score);

        $attempt = QuizAttempt::create([
            'device_id' => $device->id,
            'quiz_id' => $quiz->id,
            'answers' => ['answers' => $submittedAnswers],
            'score' => $score,
            'passed' => $passed,
            'completed_at' => now(),
        ]);

        session()->forget('quiz_attempt');

        if ($passed) {
            try {
                $this->timeGrantingService->grantTimeFromQuiz($device, $attempt);
            } catch (\Exception $e) {
                Log::error('Failed to grant time after quiz: ' . $e->getMessage());
            }
        }

        return redirect()->route('portal.quiz.result', $attempt);
    }

    public function submitVideoWords(Request $request): RedirectResponse
    {
        $device = $this->getDevice($request);
        $completionId = session('video_completion_id');

        if (!$device || !$completionId) {
            return redirect()->route('portal.landing')
                ->with('error', 'Session expired. Please try again.');
        }

        $completion = VideoCompletion::findOrFail($completionId);

        if ($completion->device_id !== $device->id) {
            return redirect()->route('portal.landing')
                ->with('error', 'Invalid session.');
        }

        $video = $completion->video;

        if (!$video) {
            Log::error('Video not found for completion', [
                'completion_id' => $completion->id,
                'video_id' => $completion->video_id,
            ]);
            return redirect()->route('portal.landing')
                ->with('error', 'Video not found. Please try again.');
        }

        $wordsEnteredInput = $request->input('words', '');

        if (is_string($wordsEnteredInput)) {
            $wordsEntered = array_map('trim', explode(',', $wordsEnteredInput));
            $wordsEntered = array_filter($wordsEntered);
        } else {
            $wordsEntered = is_array($wordsEnteredInput) ? $wordsEnteredInput : [];
        }

        $wordsShown = $completion->getWordsShown();
        $validationResult = $this->videoWordService->validateWords($wordsShown, $wordsEntered);

        $completion->update([
            'completed_at' => now(),
            'words_entered' => json_encode($wordsEntered),
            'words_correct' => $validationResult['words_correct'],
            'passed_validation' => $validationResult['passed_validation'],
        ]);

        $completion->refresh();

        if ($validationResult['passed_validation']) {
            try {
                $this->timeGrantingService->grantTimeFromVideo($device, $completion);

                Log::info('Time granted successfully after video completion', [
                    'device_id' => $device->id,
                    'video_completion_id' => $completion->id,
                    'time_granted' => $video->time_reward_minutes,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to grant time after video', [
                    'device_id' => $device->id,
                    'video_completion_id' => $completion->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        session()->forget('video_completion_id');

        return redirect()->route('portal.video.result', $completion);
    }
}

A.5	NoDogSplash Service

NoDogSplashService manages captive portal redirects using NoDogSplash. When a device's time expires, it redirects that device to the portal. After children finish quizzes or videos, it allows devices through.

<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Log;

class NoDogSplashService
{
    protected ScriptExecutor $scriptExecutor;

    public function __construct(ScriptExecutor $scriptExecutor)
    {
        $this->scriptExecutor = $scriptExecutor;
    }

    public function redirectDeviceToPortal(Device $device): bool
    {
        $macAddress = $device->mac_address;

        if (empty($macAddress)) {
            Log::error('Cannot redirect device: MAC address is missing', [
                'device_id' => $device->id,
                'device_name' => $device->name,
            ]);

            return false;
        }

        $portalBaseUrl = config('portal.url', 'http://192.168.4.1');
        $portalPath = route('portal.landing', ['mac' => $macAddress], false);
        $portalUrl = $portalBaseUrl . $portalPath;

        $result = $this->scriptExecutor->execute('redirect_device_portal.sh', [
            $macAddress,
            $portalUrl,
        ]);

        if ($result['success']) {
            Log::info('Device redirected to portal successfully', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'portal_url' => $portalUrl,
                'script_output' => $result['output'],
            ]);

            return true;
        } else {
            Log::error('Failed to redirect device to portal', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'portal_url' => $portalUrl,
                'script_error' => $result['error'],
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
            ]);

            return false;
        }
    }

    public function allowDeviceThrough(Device $device): bool
    {
        $macAddress = $device->mac_address;

        if (empty($macAddress)) {
            Log::error('Cannot allow device through: MAC address is missing', [
                'device_id' => $device->id,
                'device_name' => $device->name,
            ]);

            return false;
        }

        $result = $this->scriptExecutor->execute('allow_device_through.sh', [
            $macAddress,
        ]);

        if ($result['success']) {
            Log::info('Device allowed through successfully (redirect removed)', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'remaining_time_minutes' => $device->remaining_time_minutes,
                'script_output' => $result['output'],
            ]);

            return true;
        } else {
            Log::error('Failed to allow device through (remove redirect)', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'remaining_time_minutes' => $device->remaining_time_minutes,
                'script_error' => $result['error'],
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
            ]);

            return false;
        }
    }

    public function isDeviceRedirected(Device $device): bool
    {
        $macAddress = $device->mac_address;

        if (empty($macAddress)) {
            Log::warning('Cannot check redirect status: MAC address is missing', [
                'device_id' => $device->id,
                'device_name' => $device->name,
            ]);

            return false;
        }

        $result = $this->scriptExecutor->execute('check_device_redirected.sh', [
            $macAddress,
        ]);

        $isRedirected = $result['success'];

        Log::debug('Device redirect status checked', [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'mac_address' => $macAddress,
            'is_redirected' => $isRedirected,
            'script_output' => $result['output'],
            'return_code' => $result['return_code'],
        ]);

        return $isRedirected;
    }
}

A.6	Domain Blocking Service (Key Methods)

DomainBlockingService blocks websites at the domain level and app level using DNS (dnsmasq). This section shows key methods that detect related domains and block domains for devices.

<?php

namespace App\Services;

use App\Models\BlockedWebsite;
use App\Models\Device;
use Illuminate\Support\Facades\Log;

class DomainBlockingService
{
    protected array $appDomainMappings = [
        'facebook.com' => [
            'api.facebook.com',
            'graph.facebook.com',
            'm.facebook.com',
            'connect.facebook.com',
            'www.facebook.com',
        ],
        'instagram.com' => [
            'api.instagram.com',
            'i.instagram.com',
            'www.instagram.com',
            'graph.instagram.com',
        ],
        'tiktok.com' => [
            'api.tiktok.com',
            'www.tiktok.com',
            'm.tiktok.com',
        ],
    ];

    public function detectRelatedDomains(string $domain, ?string $appName = null): array
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^www\./', '', $domain);
        
        if (isset($this->appDomainMappings[$domain])) {
            return $this->appDomainMappings[$domain];
        }
        
        return [];
    }

    public function blockDomainForDevice(BlockedWebsite $blockedWebsite, Device $device): bool
    {
        try {
            $domainsToBlock = $blockedWebsite->getDomainsToBlock();
            
            Log::info("Blocking domains for device", [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $device->mac_address,
                'domains' => $domainsToBlock,
            ]);
            
            return $this->updateDnsmasqBlocklist($device);
            
        } catch (\Exception $e) {
            Log::error("Exception while blocking domain for device", [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function updateDnsmasqBlocklist(Device $device): bool
    {
        $blockedWebsites = BlockedWebsite::where('device_id', $device->id)->get();
        
        $domainsInput = '';
        foreach ($blockedWebsites as $blockedWebsite) {
            $domainsToBlock = $blockedWebsite->getDomainsToBlock();
            $blockSubdomains = $blockedWebsite->shouldBlockSubdomains() ? '1' : '0';
            
            foreach ($domainsToBlock as $domain) {
                $domainsInput .= $domain . ':' . $blockSubdomains . "\n";
            }
        }
        
        return true;
    }
}

A.7	Video Word Service (Key Methods)

VideoWordService handles dictionary word operations for video playback. Key methods here generate random timestamps and validate words children enter.

<?php

namespace App\Services;

class VideoWordService
{
    public function generateRandomTimestamps(int $durationSeconds, int $wordCount): array
    {
        if ($wordCount <= 0 || $durationSeconds <= 0) {
            return [];
        }

        $interval = $durationSeconds / $wordCount;

        $timestamps = [];
        for ($i = 0; $i < $wordCount; $i++) {
            $intervalStart = $i * $interval;
            $intervalEnd = ($i + 1) * $interval;

            $timestamp = (int) rand($intervalStart, $intervalEnd);

            if ($timestamp >= $durationSeconds) {
                $timestamp = $durationSeconds - 1;
            }

            $timestamps[] = $timestamp;
        }

        sort($timestamps);

        return $timestamps;
    }

    public function validateWords(array $wordsShown, array $wordsEntered): array
    {
        $normalizedShown = array_map(function ($word) {
            return strtolower(trim($word));
        }, $wordsShown);

        $normalizedEntered = array_map(function ($word) {
            return strtolower(trim($word));
        }, $wordsEntered);

        $wordsCorrect = count(array_intersect($normalizedShown, $normalizedEntered));

        $wordsShownCount = count($normalizedShown);
        $wordsEnteredCount = count($normalizedEntered);

        $passedValidation = ($wordsCorrect === $wordsShownCount) && ($wordsShownCount > 0);

        return [
            'words_shown_count' => $wordsShownCount,
            'words_entered_count' => $wordsEnteredCount,
            'words_correct' => $wordsCorrect,
            'passed_validation' => $passedValidation,
        ];
    }
}

A.8	Device Model Key Methods

The Device model has methods that manage time, check status, and handle sessions.

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    public function activeSession(): ?DeviceSession
    {
        return $this->sessions()
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();
    }

    public function hasRemainingTime(): bool
    {
        return $this->remaining_time_minutes > 0;
    }

    public function hasTimeExpired(): bool
    {
        return $this->remaining_time_minutes <= 0;
    }

    public function getRemainingTimeFormatted(): string
    {
        $hours = floor($this->remaining_time_minutes / 60);
        $minutes = $this->remaining_time_minutes % 60;

        if ($hours > 0) {
            return sprintf('%d hour%s %d minute%s', 
                $hours, 
                $hours > 1 ? 's' : '',
                $minutes, 
                $minutes !== 1 ? 's' : ''
            );
        }

        return sprintf('%d minute%s', $minutes, $minutes !== 1 ? 's' : '');
    }

    public function grantTime(int $minutes, string $source, ?int $sourceId = null): DeviceTimeGrant
    {
        $this->increment('remaining_time_minutes', $minutes);
        $this->increment('total_time_allocated', $minutes);

        $timeGrant = $this->timeGrants()->create([
            'minutes_granted' => $minutes,
            'source' => $source,
            'source_id' => $sourceId,
            'granted_at' => now(),
        ]);

        return $timeGrant;
    }

    public function deductTime(int $minutes): void
    {
        $this->decrement('remaining_time_minutes', max(0, $minutes));
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function isWhitelisted(): bool
    {
        return $this->status === 'whitelisted';
    }
}

A.9	Check Time Expiration Job

CheckTimeExpiration runs periodically. It finds devices with expired internet time and blocks them.

<?php

namespace App\Jobs;

use App\Models\Device;
use App\Services\NetworkService;
use App\Services\NoDogSplashService;
use App\Services\TimeTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckTimeExpiration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(
        TimeTrackingService $timeTrackingService,
        NetworkService $networkService,
        NoDogSplashService $noDogSplashService
    ): void {
        Log::info('CheckTimeExpiration job started - checking for expired devices');

        $expiredDevices = $timeTrackingService->getExpiredDevices();

        if ($expiredDevices->isEmpty()) {
            Log::debug('CheckTimeExpiration job completed - no expired devices found');
            return;
        }

        Log::info('CheckTimeExpiration job found expired devices', [
            'count' => $expiredDevices->count(),
        ]);

        foreach ($expiredDevices as $device) {
            try {
                Log::info('Processing expired device', [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'mac_address' => $device->mac_address,
                    'remaining_time_minutes' => $device->remaining_time_minutes,
                ]);

                $device->update(['status' => 'blocked']);

                $networkBlocked = $networkService->blockDevice($device);
                $redirectConfigured = $noDogSplashService->redirectDeviceToPortal($device);

                Log::info('Expired device processed successfully', [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'mac_address' => $device->mac_address,
                    'status' => 'blocked',
                    'network_blocked' => $networkBlocked,
                    'redirect_configured' => $redirectConfigured,
                ]);

            } catch (\Exception $e) {
                Log::error('Error processing expired device', [
                    'device_id' => $device->id ?? 'unknown',
                    'device_name' => $device->name ?? 'unknown',
                    'mac_address' => $device->mac_address ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                continue;
            }
        }

        Log::info('CheckTimeExpiration job completed', [
            'expired_devices_processed' => $expiredDevices->count(),
        ]);
    }
}

A.10	Shell Scripts

A.10.1	Block Device Script

block_device.sh blocks a device's MAC address using iptables firewall rules.

#!/bin/bash

set -e
set -u

validate_mac_address() {
    local mac="$1"
    
    if [ -z "$mac" ]; then
        echo "Error: MAC address is required" >&2
        return 1
    fi
    
    if echo "$mac" | grep -qE '^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$'; then
        return 0
    else
        echo "Error: Invalid MAC address format: $mac" >&2
        return 1
    fi
}

normalize_mac_address() {
    local mac="$1"
    mac=$(echo "$mac" | sed 's/-/:/g')
    mac=$(echo "$mac" | tr '[:lower:]' '[:upper:]')
    echo "$mac"
}

check_rule_exists() {
    local chain="$1"
    local mac="$2"
    
    if sudo iptables -L "$chain" -n -v | grep -q "$mac"; then
        return 0
    else
        return 1
    fi
}

add_block_rule() {
    local chain="$1"
    local mac="$2"
    
    if check_rule_exists "$chain" "$mac"; then
        echo "Info: Blocking rule already exists in $chain chain for MAC $mac" >&2
        return 0
    fi
    
    if sudo iptables -A "$chain" -i wlan0 -m mac --mac-source "$mac" -j DROP; then
        echo "Success: Added blocking rule to $chain chain for MAC $mac" >&2
        return 0
    else
        echo "Error: Failed to add blocking rule to $chain chain for MAC $mac" >&2
        return 1
    fi
}

if [ $# -ne 1 ]; then
    echo "Usage: $0 <MAC_ADDRESS>" >&2
    exit 1
fi

MAC_ADDRESS="$1"

if ! validate_mac_address "$MAC_ADDRESS"; then
    exit 1
fi

NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")

echo "Blocking device with MAC address: $NORMALIZED_MAC" >&2

if ! add_block_rule "INPUT" "$NORMALIZED_MAC"; then
    echo "Error: Failed to block device on INPUT chain" >&2
    exit 2
fi

if ! add_block_rule "FORWARD" "$NORMALIZED_MAC"; then
    echo "Error: Failed to block device on FORWARD chain" >&2
    exit 2
fi

echo "Device blocked successfully on both INPUT and FORWARD chains" >&2
exit 0

A.10.2	Redirect Device Portal Script

redirect_device_portal.sh sets up NoDogSplash to redirect a device to the portal page.

#!/bin/bash

set -e
set -u

NDSCTL="/usr/bin/ndsctl"

validate_mac_address() {
    local mac="$1"
    
    if [ -z "$mac" ]; then
        echo "Error: MAC address is required" >&2
        return 1
    fi
    
    if echo "$mac" | grep -qE '^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$'; then
        return 0
    else
        echo "Error: Invalid MAC address format: $mac" >&2
        return 1
    fi
}

normalize_mac_address() {
    local mac="$1"
    mac=$(echo "$mac" | sed 's/-/:/g')
    mac=$(echo "$mac" | tr '[:lower:]' '[:upper:]')
    echo "$mac"
}

find_device_token() {
    local mac="$1"
    
    if [ ! -f "$NDSCTL" ]; then
        echo "Error: ndsctl not found at $NDSCTL" >&2
        return 1
    fi
    
    local client_info=$(sudo "$NDSCTL" clients 2>/dev/null || echo "")
    
    if [ -z "$client_info" ]; then
        echo "Warning: No clients found in NoDogSplash or ndsctl failed" >&2
        return 1
    fi
    
    local token=""
    local current_mac=""
    local in_client_block=false
    local mac_lower=$(echo "$mac" | tr '[:upper:]' '[:lower:]')
    
    while IFS= read -r line; do
        if [[ "$line" =~ ^client_id= ]]; then
            in_client_block=true
            current_mac=""
            token=""
        elif [[ "$line" =~ ^mac= ]]; then
            current_mac=$(echo "$line" | sed 's/^mac=//' | tr '[:upper:]' '[:lower:]')
        elif [[ "$line" =~ ^token= ]]; then
            token=$(echo "$line" | sed 's/^token=//')
        elif [ -z "$line" ]; then
            if [ "$in_client_block" = true ] && [ "$current_mac" = "$mac_lower" ]; then
                echo "$token"
                return 0
            fi
            in_client_block=false
            current_mac=""
            token=""
        fi
    done <<< "$client_info"
    
    if [ "$in_client_block" = true ] && [ "$current_mac" = "$mac_lower" ]; then
        echo "$token"
        return 0
    fi
    
    return 1
}

deauthenticate_device() {
    local token="$1"
    
    if [ -z "$token" ]; then
        echo "Error: Token is required for deauthentication" >&2
        return 1
    fi
    
    if sudo "$NDSCTL" deauth "$token" >/dev/null 2>&1; then
        echo "Info: Device deauthenticated successfully (token: $token)" >&2
        return 0
    else
        echo "Error: Failed to deauthenticate device (token: $token)" >&2
        return 1
    fi
}

if [ $# -ne 2 ]; then
    echo "Usage: $0 <MAC_ADDRESS> <PORTAL_URL>" >&2
    exit 1
fi

MAC_ADDRESS="$1"
PORTAL_URL="$2"

if ! validate_mac_address "$MAC_ADDRESS"; then
    exit 1
fi

NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")

echo "Configuring NoDogSplash to redirect device to portal" >&2
echo "  MAC Address: $NORMALIZED_MAC" >&2
echo "  Portal URL: $PORTAL_URL" >&2

set +e
DEVICE_TOKEN=$(find_device_token "$NORMALIZED_MAC")
TOKEN_STATUS=$?
set -e

if [ $TOKEN_STATUS -ne 0 ] || [ -z "$DEVICE_TOKEN" ]; then
    echo "Error: Device not found in NoDogSplash client list" >&2
    exit 2
fi

echo "Info: Found device token: $DEVICE_TOKEN" >&2

if ! deauthenticate_device "$DEVICE_TOKEN"; then
    echo "Error: Failed to deauthenticate device" >&2
    exit 3
fi

echo "Device redirect configured successfully" >&2
exit 0

