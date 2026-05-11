<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Video;
use App\Models\VideoCompletion;
use App\Models\VideoWordDisplay;
use App\Services\DeviceService;
use App\Services\PortalActivityRecommendationService;
use App\Services\TimeGrantingService;
use App\Services\VideoWordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PortalController extends Controller
{
    /**
     * Wrong word submissions allowed before the child must re-watch the video.
     */
    private const VIDEO_WORD_GUESS_MAX_FAILURES = 3;

    protected TimeGrantingService $timeGrantingService;

    protected VideoWordService $videoWordService;

    protected DeviceService $deviceService;

    protected PortalActivityRecommendationService $portalRecommendations;

    public function __construct(
        TimeGrantingService $timeGrantingService,
        VideoWordService $videoWordService,
        DeviceService $deviceService,
        PortalActivityRecommendationService $portalRecommendations,
    ) {
        $this->timeGrantingService = $timeGrantingService;
        $this->videoWordService = $videoWordService;
        $this->deviceService = $deviceService;
        $this->portalRecommendations = $portalRecommendations;
    }

    /**
     * Display portal landing page.
     *
     * Route: GET /portal?mac=AA:BB:CC:DD:EE:FF
     * Route Name: portal.landing
     *
     * What it does:
     * 1. Identifies child's device by MAC address from URL query parameter
     * 2. Fetches all active quizzes assigned to this device
     * 3. Fetches all active videos assigned to this device
     * 4. Displays landing page showing available activities
     *
     * This is the main entry point for children accessing the portal.
     * Children see a list of quizzes and videos they can complete to earn internet time.
     *
     * How it works:
     * - Device is identified by MAC address (unique device identifier)
     * - Only active quizzes/videos assigned to this device are shown
     * - Each activity shows time reward (minutes child will earn)
     * - Child clicks on activity to start it
     *
     * Security:
     * - No authentication required (captive portal)
     * - Device must exist in database
     * - Only shows activities assigned to this specific device
     *
     * @param  Request  $request  HTTP request (contains MAC address in ?mac= parameter)
     * @return View Portal landing page with available activities
     *
     * Usage Example:
     * URL: http://example.com/portal?mac=AA:BB:CC:DD:EE:FF
     * - Gets device with MAC address AA:BB:CC:DD:EE:FF
     * - Shows all quizzes and videos assigned to that device
     */
    public function landing(Request $request): View
    {
        // Get device from MAC address in request
        // getDevice() looks for MAC in URL query (?mac=...), POST data, or session
        $device = $this->getDevice($request);

        // Unknown device on home Wi‑Fi: show simplified registration request (Simplify_project.md).
        // No MAC on wire: plain-language hint to connect / trigger captive sign-in.
        if (! $device) {
            $showDeviceRegistration = session('device_mac') !== null;

            return view('portal.landing', [
                'device' => null,
                'showDeviceRegistration' => $showDeviceRegistration,
                'error' => $showDeviceRegistration ? null : 'We could not tell which device this is. Connect to the home Wi‑Fi, open a website once to sign in, then open this page again.',
            ]);
        }

        $flow = $request->query('flow', 'chooser');
        if (! in_array($flow, ['chooser', 'quiz', 'video', 'quiz_more', 'video_more'], true)) {
            $flow = 'chooser';
        }

        $eligibleQuizzes = $this->portalRecommendations->eligibleQuizzes($device);
        $eligibleVideos = $this->portalRecommendations->eligibleVideos($device);
        $recommendedQuiz = $this->portalRecommendations->recommendQuiz($device);
        $recommendedVideo = $this->portalRecommendations->recommendVideo($device);
        $quizSubjectSections = $this->portalRecommendations->quizzesGroupedBySubject($eligibleQuizzes);
        $randomMixEligible = $this->portalRecommendations->randomMixEligible($device);
        $randomModeQuiz = $this->portalRecommendations->randomModeQuiz($device);

        return view('portal.landing', [
            'device' => $device,
            'flow' => $flow,
            'eligibleQuizzes' => $eligibleQuizzes,
            'eligibleVideos' => $eligibleVideos,
            'recommendedQuiz' => $recommendedQuiz,
            'recommendedVideo' => $recommendedVideo,
            'quizSubjectSections' => $quizSubjectSections,
            'randomMixEligible' => $randomMixEligible,
            'randomModeQuiz' => $randomModeQuiz,
        ]);
    }

    /**
     * Get device from request (by MAC address, token, or session).
     *
     * This is a helper method used by all portal methods to identify which
     * child's device is accessing the portal.
     *
     * How it works:
     * 1. Tries to get NoDogSplash token from URL query parameter (?tok=...)
     *    - If token found, looks up MAC address from NoDogSplash client list
     * 2. If no token, tries to get MAC address from URL query parameter (?mac=AA:BB:CC:DD:EE:FF)
     * 3. If not found, tries form input (POST data)
     * 4. If not found, tries session (stored from previous request)
     * 5. Looks up device in database by MAC address
     *
     * Why MAC address? Each device has a unique MAC address (like a fingerprint).
     * This identifies which child's device is taking the quiz, even without login.
     *
     * Why token support? NoDogSplash passes a token parameter when redirecting
     * from the splash page. We can look up the MAC address from the token.
     *
     * @param  Request  $request  The HTTP request
     * @return Device|null The device if found, null if not found
     */
    /**
     * Resolve the client MAC (NoDogSplash token, gateway IP lookup, query, or session) and persist to session.
     */
    protected function resolveMacAddress(Request $request): ?string
    {
        $token = $request->query('tok');
        if ($token) {
            $macAddress = $this->getMacFromToken($token);
            if ($macAddress) {
                $normalized = $this->deviceService->normalizeMacAddress($macAddress);
                session(['device_mac' => $normalized]);

                return $normalized;
            }
        }

        $clientIp = $request->ip();
        if ($clientIp && $clientIp !== '127.0.0.1' && $clientIp !== '::1') {
            $macAddress = $this->getMacFromIp($clientIp);
            if ($macAddress) {
                $normalized = $this->deviceService->normalizeMacAddress($macAddress);
                session(['device_mac' => $normalized]);

                return $normalized;
            }
        }

        $macAddress = $request->query('mac')
            ?? $request->input('mac')
            ?? session('device_mac');

        if (! $macAddress) {
            $macAddress = $this->devLoopbackMacAddress($request);
        }

        if (! $macAddress) {
            return null;
        }

        $normalized = $this->deviceService->normalizeMacAddress($macAddress);
        session(['device_mac' => $normalized]);

        return $normalized;
    }

    /**
     * When developing with `php artisan serve`, the client is loopback and ndsctl is absent.
     * PORTAL_DEV_CLIENT_MAC (see config/portal.php) supplies a test MAC only in that case.
     */
    protected function devLoopbackMacAddress(Request $request): ?string
    {
        if (! app()->environment(['local', 'testing'])) {
            return null;
        }

        $devMac = config('portal.dev_client_mac');
        if (! is_string($devMac) || trim($devMac) === '') {
            return null;
        }

        $ip = $request->ip();
        if (! in_array($ip, ['127.0.0.1', '::1'], true)) {
            return null;
        }

        return trim($devMac);
    }

    protected function getDevice(Request $request): ?Device
    {
        $macAddress = $this->resolveMacAddress($request);

        if (! $macAddress) {
            Log::warning('Device not found - no token, IP lookup failed, and no MAC in request', [
                'ip' => $request->ip() ?? 'unknown',
                'token' => $request->query('tok') ?? 'none',
                'url' => $request->fullUrl(),
            ]);

            return null;
        }

        return Device::where('mac_address', $macAddress)->first();
    }

    /**
     * Redirect to portal landing when the client is known on Wi‑Fi but not registered, or unknown.
     */
    protected function redirectLandingUnknownDevice(): RedirectResponse
    {
        if (session('device_mac')) {
            return redirect()->route('portal.landing')
                ->with('portal_info', 'This device is not set up yet. Ask a parent to approve it, or use the form below to send a request.');
        }

        return redirect()->route('portal.landing')
            ->with('error', 'We could not tell which device this is. Connect to the home Wi‑Fi, open a website once to sign in, then open this page again.');
    }

    /**
     * Get MAC address from NoDogSplash token.
     *
     * NoDogSplash assigns a unique token to each client device. We can use
     * the `ndsctl clients` command to look up the MAC address associated with
     * a token.
     *
     * How it works:
     * 1. Executes `ndsctl clients` command to get list of all connected clients
     * 2. Parses output to find the line containing the token
     * 3. Extracts MAC address from that line
     * 4. Returns MAC address in lowercase format (e.g., "e6:6a:8f:19:be:b1")
     *
     * Output format from ndsctl clients:
     * client_id=0 ip=192.168.4.32 mac=e6:6a:8f:19:be:b1 ... token=abc123
     *
     * @param  string  $token  The NoDogSplash token
     * @return string|null The MAC address if found, null if not found
     */
    protected function getMacFromToken(string $token): ?string
    {
        // Execute ndsctl clients command to get list of all connected clients
        // This requires sudo, so we use shell_exec with proper error handling
        $output = @shell_exec('sudo ndsctl clients 2>/dev/null');

        if (! $output) {
            // Command failed or no output
            Log::warning('Failed to execute ndsctl clients', [
                'token' => $token,
            ]);

            return null;
        }

        // Parse multi-line output to find token and extract MAC
        // Format:
        // client_id=0
        // ip=192.168.4.32
        // mac=e6:6a:8f:19:be:b1
        // token=74b99472
        // state=Preauthenticated
        $lines = explode("\n", trim($output));

        $currentMac = null;
        $inClientBlock = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines (end of client block)
            if (empty($line)) {
                $inClientBlock = false;
                $currentMac = null;

                continue;
            }

            // Check if this is the start of a new client block
            if (strpos($line, 'client_id=') === 0) {
                $inClientBlock = true;
                $currentMac = null;

                continue;
            }

            // If we're in a client block, look for MAC and token
            if ($inClientBlock) {
                // Extract MAC address (remove "mac=" prefix)
                if (strpos($line, 'mac=') === 0) {
                    $currentMac = strtolower(substr($line, 4)); // Remove "mac=" prefix
                }

                // Check if this line contains the token we're looking for
                if (strpos($line, "token=$token") === 0) {
                    // Found the token! Return the MAC we collected
                    if ($currentMac) {
                        return $currentMac;
                    }
                }
            }
        }

        // Token not found in client list
        Log::warning('Token not found in NoDogSplash client list', [
            'token' => $token,
            'output_sample' => substr($output, 0, 200), // Log first 200 chars for debugging
        ]);

        return null;
    }

    /**
     * Get MAC address from IP address using NoDogSplash client list.
     *
     * This method looks up the MAC address associated with a given IP address
     * by querying NoDogSplash's client list. This is useful when devices access
     * the portal directly (e.g., Android captive portal detection) without
     * going through the splash page (no token available).
     *
     * How it works:
     * 1. Executes `ndsctl clients` command to get list of all connected clients
     * 2. Parses output to find the client with matching IP address
     * 3. Extracts MAC address from that client block
     * 4. Returns MAC address in lowercase format (e.g., "e6:6a:8f:19:be:b1")
     *
     * Output format from ndsctl clients:
     * client_id=0
     * ip=192.168.4.31
     * mac=e6:6a:8f:19:be:b1
     * token=f7fadfb9
     * state=Preauthenticated
     *
     * @param  string  $ipAddress  The IP address to look up
     * @return string|null The MAC address if found, null if not found
     */
    protected function getMacFromIp(string $ipAddress): ?string
    {
        // Execute ndsctl clients command to get list of all connected clients
        // This requires sudo, so we use shell_exec with proper error handling
        $output = @shell_exec('sudo ndsctl clients 2>/dev/null');

        if (! $output) {
            // Command failed or no output
            Log::warning('Failed to execute ndsctl clients for IP lookup', [
                'ip' => $ipAddress,
            ]);

            return null;
        }

        // Parse multi-line output to find IP and extract MAC
        // Format:
        // client_id=0
        // ip=192.168.4.31
        // mac=e6:6a:8f:19:be:b1
        // token=f7fadfb9
        // state=Preauthenticated
        $lines = explode("\n", trim($output));

        $currentMac = null;
        $currentIp = null;
        $inClientBlock = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines (end of client block)
            if (empty($line)) {
                // Check if we found our IP before moving to next block
                if ($inClientBlock && $currentIp === $ipAddress && $currentMac) {
                    return strtolower($currentMac);
                }
                $inClientBlock = false;
                $currentMac = null;
                $currentIp = null;

                continue;
            }

            // Check if this is the start of a new client block
            if (strpos($line, 'client_id=') === 0) {
                // Check previous block before starting new one
                if ($inClientBlock && $currentIp === $ipAddress && $currentMac) {
                    return strtolower($currentMac);
                }
                $inClientBlock = true;
                $currentMac = null;
                $currentIp = null;

                continue;
            }

            // If we're in a client block, look for IP and MAC
            if ($inClientBlock) {
                // Extract IP address (remove "ip=" prefix)
                if (strpos($line, 'ip=') === 0) {
                    $currentIp = substr($line, 3); // Remove "ip=" prefix
                }

                // Extract MAC address (remove "mac=" prefix)
                if (strpos($line, 'mac=') === 0) {
                    $currentMac = substr($line, 4); // Remove "mac=" prefix
                }

                // If we have both IP and MAC, and IP matches, return immediately
                if ($currentIp === $ipAddress && $currentMac) {
                    return strtolower($currentMac);
                }
            }
        }

        // Check last client block if file doesn't end with newline
        if ($inClientBlock && $currentIp === $ipAddress && $currentMac) {
            return strtolower($currentMac);
        }

        // IP not found in client list
        Log::debug('IP address not found in NoDogSplash client list', [
            'ip' => $ipAddress,
        ]);

        return null;
    }

    /**
     * Display quiz for child to take.
     *
     * Route: GET /portal/quiz/{quiz}?mac=AA:BB:CC:DD:EE:FF
     *
     * What it does:
     * 1. Identifies the child's device by MAC address
     * 2. Validates quiz is active and assigned to device
     * 3. Stores quiz attempt in session (temporary storage)
     * 4. Displays quiz interface for child to answer questions
     *
     * Security checks:
     * - Device must exist in database
     * - Quiz must be active (is_active = true)
     * - Device must be assigned to quiz (many-to-many relationship)
     *
     * Session storage: Stores quiz data temporarily so we can track progress
     * as child answers questions. Session is cleared when quiz is submitted.
     *
     * @param  Request  $request  HTTP request (contains MAC address)
     * @param  Quiz  $quiz  The quiz to display (found by ID from URL)
     * @return View|RedirectResponse Quiz interface or redirect if validation fails
     */
    public function showQuiz(Request $request, Quiz $quiz): View|RedirectResponse
    {
        // Get device from MAC address in request
        $device = $this->getDevice($request);

        // Validation: Device must exist
        if (! $device) {
            return $this->redirectLandingUnknownDevice();
        }

        // Validation: Quiz must be active
        // Parents can deactivate quizzes to prevent children from taking them
        if (! $quiz->is_active) {
            return redirect()->route('portal.landing')
                ->with('error', 'This quiz is not available.');
        }

        // Validation: Device must be assigned to this quiz
        // ->quizzes gets all quizzes assigned to this device (relationship)
        // ->contains($quiz) checks if this specific quiz is in that list
        if (! $device->quizzes->contains($quiz)) {
            return redirect()->route('portal.landing')
                ->with('error', 'You do not have access to this quiz.');
        }

        // Max passed completions per device per calendar day (only counts attempts where passed = true)
        if ($quiz->max_passes_per_day) {
            $passesToday = QuizAttempt::query()
                ->where('device_id', $device->id)
                ->where('quiz_id', $quiz->id)
                ->where('passed', true)
                ->whereDate('completed_at', now()->toDateString())
                ->count();

            if ($passesToday >= (int) $quiz->max_passes_per_day) {
                return redirect()->route('portal.landing', ['mac' => $device->mac_address])
                    ->with('error', 'You have reached the maximum successful completions for this quiz today. Try again tomorrow.');
            }
        }

        // Minimum wait after any finished attempt before starting again
        if ($quiz->retry_cooldown_minutes) {
            $lastAttempt = QuizAttempt::query()
                ->where('device_id', $device->id)
                ->where('quiz_id', $quiz->id)
                ->orderByDesc('completed_at')
                ->first();

            if ($lastAttempt && $lastAttempt->completed_at) {
                $eligibleAt = $lastAttempt->completed_at->copy()->addMinutes((int) $quiz->retry_cooldown_minutes);
                if (now()->lt($eligibleAt)) {
                    $minutesLeft = max(1, (int) ceil(now()->diffInSeconds($eligibleAt) / 60));

                    return redirect()->route('portal.landing', ['mac' => $device->mac_address])
                        ->with('error', "Please wait {$minutesLeft} more minute(s) before starting this quiz again.");
                }
            }
        }

        // Extract questions from quiz's JSON structure
        // Questions are stored as: {questions: [{id: 1, question: "...", ...}]}
        // We extract the inner array: [{id: 1, question: "...", ...}]
        $questions = $quiz->questions['questions'] ?? [];
        if ($quiz->level && $quiz->subject) {
            $questions = QuestionBankItem::queryForFixedQuiz($quiz)
                ->inRandomOrder()
                ->limit(max(1, (int) $quiz->question_count))
                ->get()
                ->map(fn (QuestionBankItem $item, int $index): array => $item->toPortalQuestionPayload($index))
                ->values()
                ->all();
        } elseif ($quiz->scoring_mode === 'time_reward') {
            $levels = $quiz->effectiveRandomBankLevelsForDevice($device);
            $questions = QuestionBankItem::queryForRandomBankMix($quiz, $levels)
                ->inRandomOrder()
                ->limit((int) ($quiz->question_count ?: 10))
                ->get()
                ->map(fn (QuestionBankItem $item, int $index): array => $item->toPortalQuestionPayload($index))
                ->values()
                ->all();
        }

        if ((($quiz->level && $quiz->subject) || $quiz->scoring_mode === 'time_reward') && count($questions) === 0) {
            return redirect()->route('portal.landing', ['mac' => $device->mac_address])
                ->with('error', 'No questions are available for this quiz right now.');
        }

        // Store quiz attempt in session for progress tracking
        // Session is temporary storage that persists across page requests
        // This allows us to track which questions child has answered
        session([
            'quiz_attempt' => [
                'quiz_id' => $quiz->id,           // Which quiz is being taken
                'device_id' => $device->id,        // Which device/child is taking it
                'questions' => $questions,         // All questions (for validation)
                'answers' => [],                   // Empty array - will be filled as child answers
                'current_question' => 0,           // Start at first question
                'started_at' => now(),             // When quiz started (timestamp)
            ],
        ]);

        // Display quiz interface
        // Passes quiz, device, and questions to the view
        return view('portal.quiz', [
            'quiz' => $quiz,
            'device' => $device,
            'questions' => $questions,
        ]);
    }

    /**
     * Process quiz submission and calculate score.
     *
     * Route: POST /portal/quiz/submit
     *
     * This is the CORE method that processes quiz answers and grants time.
     *
     * What it does:
     * 1. Gets device and quiz attempt data from session
     * 2. Retrieves child's submitted answers from form
     * 3. Compares each answer with correct answer (handles different question types)
     * 4. Calculates score as percentage (correct / total * 100)
     * 5. Determines if child passed (score >= passing_score)
     * 6. Saves attempt to database (for history/analytics)
     * 7. Grants time if passed (via TimeGrantingService)
     * 8. Redirects to results page
     *
     * Answer Comparison Logic:
     * - Multiple Choice: Converts letter (a,b,c,d) to option value, compares
     * - True/False: Direct comparison (case-insensitive)
     * - Fill-in-the-Blank: Text comparison (case-insensitive, trimmed)
     *
     * Why case-insensitive? "Paris" and "paris" should both be correct.
     * Why trimmed? " Paris " should match "Paris" (removes extra spaces).
     *
     * @param  Request  $request  HTTP request containing submitted answers
     * @return RedirectResponse Redirects to quiz result page
     */
    public function submitQuiz(Request $request): RedirectResponse
    {
        // Get device and quiz attempt data
        $device = $this->getDevice($request);
        $quizAttemptData = session('quiz_attempt');  // Data stored in showQuiz()

        // Validation: Both device and session data must exist
        if (! $device || ! $quizAttemptData) {
            return redirect()->route('portal.landing')
                ->with('error', 'Session expired. Please try again.');
        }

        // Get quiz and questions from session data
        $quiz = Quiz::findOrFail($quizAttemptData['quiz_id']);
        $questions = $quizAttemptData['questions'];

        // Get child's submitted answers from form
        // Form sends answers as array: [0 => 'a', 1 => 'Paris', 2 => 'True']
        $submittedAnswers = $request->input('answers', []);

        // Calculate score by comparing answers
        $correctCount = 0;
        $totalQuestions = count($questions);

        // Loop through each question and check if answer is correct
        foreach ($questions as $index => $question) {
            // Get child's answer for this question (default to null if not answered)
            $submittedAnswer = $submittedAnswers[$index] ?? null;
            // Get the correct answer from question data
            $correctAnswer = $question['correct_answer'];

            // Compare answers based on question type
            $isCorrect = false;

            if ($question['type'] === 'multiple_choice') {
                // Multiple Choice: Child selects letter (a, b, c, d)
                // We need to convert letter to option value and compare

                $submittedLetter = strtoupper(trim((string) $submittedAnswer));  // "A" or "B" or "C" or "D"
                $options = $question['options'] ?? [];  // ["2", "3", "4", "5"]

                // Question bank mode stores correct option directly as A/B/C/D.
                if (in_array($question['correct_answer'], ['A', 'B', 'C', 'D'], true)) {
                    $isCorrect = $submittedLetter === $question['correct_answer'];
                } else {
                    $submittedIndex = ord(strtolower($submittedLetter)) - ord('a');

                    // Get the option value the child selected
                    if (isset($options[$submittedIndex])) {
                        $submittedOptionValue = strtolower(trim($options[$submittedIndex]));  // e.g., "4"
                        $correctAnswerValue = strtolower(trim($correctAnswer));  // e.g., "4"
                        $isCorrect = $submittedOptionValue === $correctAnswerValue;
                    }
                }
            } elseif ($question['type'] === 'true_false') {
                // True/False: Direct comparison (case-insensitive)
                // "True" and "true" should both be correct
                $isCorrect = strtolower(trim($submittedAnswer)) === strtolower(trim($correctAnswer));
            } else {
                // Fill-in-the-Blank: Text comparison (case-insensitive, trimmed)
                // "Paris", "paris", " Paris " should all match "Paris"
                $isCorrect = strtolower(trim($submittedAnswer)) === strtolower(trim($correctAnswer));
            }

            // If answer is correct, increment counter
            if ($isCorrect) {
                $correctCount++;
            }
        }

        // Calculate score as percentage
        // Example: 3 correct out of 5 = (3/5) * 100 = 60%
        // round() rounds to nearest integer (60.5% becomes 61%)
        $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;

        // Check if child passed (score >= passing_score)
        // Example: If passing_score is 70% and score is 60%, passed = false
        $passed = $quiz->isPassingScore((int) $score);

        // Create quiz attempt record in database
        // This saves the attempt for history/analytics (parent can see results later)
        $attempt = QuizAttempt::create([
            'device_id' => $device->id,           // Which device took the quiz
            'quiz_id' => $quiz->id,               // Which quiz was taken
            'answers' => ['answers' => $submittedAnswers],  // Child's answers (JSON)
            'score' => $score,                    // Calculated score (0-100)
            'correct_count' => $correctCount,
            'total_questions' => $totalQuestions,
            'passed' => $passed,                  // Boolean: true if passed
            'completed_at' => now(),              // When quiz was finished
        ]);

        // Clear quiz attempt from session (no longer needed)
        session()->forget('quiz_attempt');

        // If child passed, grant additional internet time
        if ($passed) {
            try {
                if ($quiz->scoring_mode === 'time_reward') {
                    $minutes = (int) $correctCount * max(1, (int) $quiz->minutes_per_correct);
                    if ($minutes > 0) {
                        $portalSync = ['client_ip' => $request->ip() ?: ''];
                        $this->timeGrantingService->grantTime($device, $minutes, 'quiz', $attempt->id, $portalSync);
                    }
                } else {
                    $this->timeGrantingService->grantTimeFromQuiz($device, $attempt, ['client_ip' => $request->ip() ?: '']);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the request
                // Child still sees result, but time grant might have failed
                Log::error('Failed to grant time after quiz: '.$e->getMessage());
            }
        }

        // Redirect to results page
        // Child will see pass/fail status, score, and time granted (if passed)
        return redirect()->route('portal.quiz.result', $attempt);
    }

    /**
     * Show quiz result.
     *
     * Route: GET /portal/quiz/result/{attempt}
     *
     * What it does:
     * 1. Gets the quiz attempt record (contains score, pass/fail status)
     * 2. Finds the device that took the quiz
     * 3. Displays result page with different content based on pass/fail
     *
     * If Passed:
     * - Shows success message
     * - Displays score percentage
     * - Shows time granted (e.g., "You earned 15 minutes!")
     * - Auto-redirects after 3 seconds (JavaScript in view)
     *
     * If Failed:
     * - Shows failure message
     * - Displays score and required score
     * - Shows "Retry Quiz" button
     * - Does NOT show correct answers (allows fair retry)
     *
     * Why hide answers? Prevents children from memorizing answers and
     * retaking quiz immediately. They must actually learn the material.
     *
     * @param  QuizAttempt  $attempt  The quiz attempt record (found by ID from URL)
     * @return View|RedirectResponse Quiz result page or redirect if device not found
     */
    public function quizResult(QuizAttempt $attempt): View|RedirectResponse
    {
        // Get device that took the quiz
        $device = Device::find($attempt->device_id);

        // Validation: Device must exist
        if (! $device) {
            return redirect()->route('portal.landing')
                ->with('error', 'Device not found.');
        }

        // If child passed, show success page with time granted
        if ($attempt->passed) {
            $timeGranted = $attempt->quiz->scoring_mode === 'time_reward'
                ? ((int) $attempt->correct_count * max(1, (int) $attempt->quiz->minutes_per_correct))
                : (int) $attempt->quiz->time_reward_minutes;

            return view('portal.quiz-result', [
                'attempt' => $attempt,                                    // Quiz attempt data
                'device' => $device,                                      // Device data
                'timeGranted' => $timeGranted,
            ]);
        }

        // If child failed, show failure page with retry option
        // timeGranted = 0 because no time was granted
        return view('portal.quiz-result', [
            'attempt' => $attempt,
            'device' => $device,
            'timeGranted' => 0,  // No time granted for failed quiz
        ]);
    }

    /**
     * Display video for child to watch.
     *
     * Route: GET /portal/video/{video}?mac=AA:BB:CC:DD:EE:FF
     *
     * What it does:
     * 1. Identifies the child's device by MAC address
     * 2. Validates video is active and assigned to device
     * 3. Gets or creates video completion record (tracks viewing session)
     * 4. If dictionary words enabled, selects random words and generates timestamps
     * 5. Stores word displays in database (for validation later)
     * 6. Displays video player with word overlays
     *
     * Security checks:
     * - Device must exist in database
     * - Video must be active (is_active = true)
     * - Device must be assigned to video (many-to-many relationship)
     *
     * Dictionary Words:
     * - If enabled, random words are selected from dictionary pool
     * - Random timestamps are generated throughout video duration
     * - Words will appear as overlays during playback (handled by JavaScript)
     * - Words are stored in VideoWordDisplay table for validation
     *
     * Retry Logic:
     * - If child failed previous attempt, creates new attempt (increments attempt_number)
     * - New attempt gets new random words and timestamps
     * - This ensures child can't memorize words from previous attempt
     *
     * @param  Request  $request  HTTP request (contains MAC address)
     * @param  Video  $video  The video to display (found by ID from URL)
     * @return View|RedirectResponse Video player interface or redirect if validation fails
     */
    /**
     * Stream video bytes for the portal HTML5 player (same host + /portal prefix as other captive traffic).
     *
     * Uses BinaryFileResponse so Range requests work (required for reliable playback on many mobile browsers).
     */
    public function streamVideo(Request $request, Video $video): BinaryFileResponse
    {
        $device = $this->getDevice($request);

        if (! $device) {
            abort(403, 'Device not found');
        }

        if (! $video->is_active) {
            abort(403, 'Video not available');
        }

        $hasAccess = $device->videos()->where('videos.id', $video->id)->exists();
        if (! $hasAccess) {
            abort(403, 'Access denied');
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($video->video_path)) {
            Log::warning('Portal video file missing', [
                'video_id' => $video->id,
                'path' => $video->video_path,
            ]);
            abort(404, 'Video file not found');
        }

        $absolutePath = $disk->path($video->video_path);
        $mime = @mime_content_type($absolutePath) ?: $video->getMimeType();

        /*
         * Long videos: each stream request can stay open for many minutes (Range requests
         * still run PHP until that chunk is sent). Low max_execution_time (60–180s is common)
         * kills PHP mid-stream → truncated bytes → MEDIA_ERR_DECODE.
         *
         * Use a large explicit ceiling (configurable): set_time_limit(0) is ignored on some
         * Windows/hosting setups; ini_set is more reliable. Turn off output buffering so the
         * server does not hold huge buffers.
         */
        $streamMaxSec = (int) config('portal.video_stream_max_execution_seconds', 86400);
        if ($streamMaxSec < 300) {
            $streamMaxSec = 300;
        }
        @ini_set('max_execution_time', (string) $streamMaxSec);
        if (function_exists('set_time_limit')) {
            @set_time_limit($streamMaxSec);
        }
        ignore_user_abort(true);
        @ini_set('zlib.output_compression', '0');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', basename($video->video_path)).'"',
        ]);
    }

    public function showVideo(Request $request, Video $video): View|RedirectResponse
    {
        // Get device from MAC address in request
        $device = $this->getDevice($request);

        // Validation: Device must exist
        if (! $device) {
            return $this->redirectLandingUnknownDevice();
        }

        // Validation: Video must be active
        // Parents can deactivate videos to prevent children from watching them
        if (! $video->is_active) {
            return redirect()->route('portal.landing')
                ->with('error', 'This video is not available.');
        }

        // Validation: Device must be assigned to this video
        // ->videos gets all videos assigned to this device (relationship)
        // ->contains($video) checks if this specific video is in that list
        if (! $device->videos->contains($video)) {
            return redirect()->route('portal.landing')
                ->with('error', 'You do not have access to this video.');
        }

        if ($request->boolean('word_retry')
            && $video->dictionary_words_enabled
            && $video->word_count > 0) {
            $sessionCompletionId = session('video_completion_id');
            if ($sessionCompletionId) {
                $existingCompletion = VideoCompletion::query()
                    ->whereKey($sessionCompletionId)
                    ->where('device_id', $device->id)
                    ->where('video_id', $video->id)
                    ->where('passed_validation', false)
                    ->where('words_shown_count', '>', 0)
                    ->where('word_guess_failed_count', '>', 0)
                    ->where('word_guess_failed_count', '<', self::VIDEO_WORD_GUESS_MAX_FAILURES)
                    ->first();

                if ($existingCompletion) {
                    $wordsData = [];
                    $shownDictionaryWordIds = [];
                    foreach ($existingCompletion->wordDisplays()->orderBy('displayed_at_timestamp')->get() as $wd) {
                        $shownDictionaryWordIds[] = $wd->dictionary_word_id;
                        $wordsData[] = [
                            'word' => $wd->word_text,
                            'definition' => optional($wd->dictionaryWord)->definition ?? '',
                            'timestamp' => $wd->displayed_at_timestamp,
                        ];
                    }

                    $distractorWords = $this->videoWordService
                        ->selectDistractorWords(5, $shownDictionaryWordIds)
                        ->pluck('word')
                        ->values()
                        ->all();

                    session(['video_completion_id' => $existingCompletion->id]);

                    return view('portal.video', [
                        'video' => $video,
                        'device' => $device,
                        'completion' => $existingCompletion,
                        'wordsData' => $wordsData,
                        'distractorWords' => $distractorWords,
                        'openWordGameOnLoad' => true,
                        'wordRetryMode' => true,
                        'wordGuessFailedCount' => (int) $existingCompletion->word_guess_failed_count,
                        'videoWordGuessMaxFailures' => self::VIDEO_WORD_GUESS_MAX_FAILURES,
                    ]);
                }
            }
        }

        // Get or create video completion record
        // This tracks the viewing session and stores validation results
        // If child is retrying (previous attempt failed), get the latest attempt
        $latestCompletion = VideoCompletion::where('device_id', $device->id)
            ->where('video_id', $video->id)
            ->latest('attempt_number')
            ->first();

        // Calculate next attempt number
        // If no previous attempts, start at 1
        // If previous attempts exist, increment by 1
        $attemptNumber = $latestCompletion ? ($latestCompletion->attempt_number + 1) : 1;

        // Create new video completion record
        // This will store the viewing session and validation results
        $completion = VideoCompletion::create([
            'device_id' => $device->id,           // Which device is watching
            'video_id' => $video->id,              // Which video is being watched
            'attempt_number' => $attemptNumber,    // Attempt number (1, 2, 3, etc.)
            'completed_at' => null,                 // Will be set when video ends
            'watched_duration' => 0,               // Will be updated as video plays
            'words_shown_count' => 0,               // Will be set when words are displayed
            'words_entered' => null,                // Will be set when child submits words
            'words_correct' => 0,                   // Will be set after validation
            'passed_validation' => false,           // Will be set after validation
            'word_guess_failed_count' => 0,
        ]);

        // Handle dictionary words if enabled
        $wordsData = [];
        $distractorWords = [];
        if ($video->dictionary_words_enabled && $video->word_count > 0) {
            // Select random words from dictionary pool
            // VideoWordService handles the random selection logic
            $words = $this->videoWordService->selectRandomWords($video->word_count);

            // Generate random timestamps throughout video duration
            // Timestamps determine when words appear during playback
            $timestamps = $this->videoWordService->generateRandomTimestamps(
                $video->duration_seconds,
                $video->word_count
            );

            $shownDictionaryWordIds = [];

            // Store word displays in database
            // This creates records in VideoWordDisplay table for validation
            foreach ($words as $index => $word) {
                $shownDictionaryWordIds[] = $word->id;

                VideoWordDisplay::create([
                    'video_completion_id' => $completion->id,
                    'dictionary_word_id' => $word->id,
                    'displayed_at_timestamp' => $timestamps[$index] ?? 0,
                    'word_text' => $word->word,  // Store word text for easy access
                ]);

                // Prepare data for JavaScript (word overlays)
                $wordsData[] = [
                    'word' => $word->word,
                    'definition' => $word->definition,
                    'timestamp' => $timestamps[$index] ?? 0,
                ];
            }

            $distractorWords = $this->videoWordService
                ->selectDistractorWords(5, $shownDictionaryWordIds)
                ->pluck('word')
                ->values()
                ->all();

            // Update completion with word count
            $completion->update([
                'words_shown_count' => count($wordsData),
            ]);
        }

        // Store completion ID in session for later retrieval
        // This allows us to find the completion record when child submits words
        session(['video_completion_id' => $completion->id]);

        // Display video player interface
        // Passes video, device, completion, and words data to the view
        return view('portal.video', [
            'video' => $video,
            'device' => $device,
            'completion' => $completion,
            'wordsData' => $wordsData,  // Array of words with timestamps for JavaScript
            'distractorWords' => $distractorWords,
            'openWordGameOnLoad' => false,
            'wordRetryMode' => false,
            'wordGuessFailedCount' => 0,
            'videoWordGuessMaxFailures' => self::VIDEO_WORD_GUESS_MAX_FAILURES,
        ]);
    }

    /**
     * Process video word submission and validate words.
     *
     * Route: POST /portal/video/submit-words
     *
     * This is the CORE method that processes word validation and grants time.
     *
     * What it does:
     * 1. Gets device and video completion from session
     * 2. Retrieves child's entered words from form
     * 3. Gets words that were actually displayed during video
     * 4. Validates entered words against displayed words (case-insensitive, trimmed, order must match playback)
     * 5. Updates completion record with validation results
     * 6. Grants time if validation passed (via TimeGrantingService)
     * 7. Redirects to results page
     *
     * Word Validation:
     * - Case-insensitive: "Adventure" = "adventure" = "ADVENTURE"
     * - Trimmed: " adventure " = "adventure"
     * - All words must be correct (no partial credit)
     * - Uses VideoWordService for validation logic
     *
     * Retry Logic:
     * - If validation fails, child can retry
     * - New attempt creates new random words and timestamps
     * - Previous attempt is preserved in database (for history)
     *
     * @param  Request  $request  HTTP request containing entered words
     * @return RedirectResponse Redirects to video result page
     */
    public function submitVideoWords(Request $request): RedirectResponse
    {
        // Get device from MAC address in request
        $device = $this->getDevice($request);

        // Get video completion ID from session (stored in showVideo())
        $completionId = session('video_completion_id');

        // Validation: Both device and completion must exist
        if (! $device || ! $completionId) {
            return redirect()->route('portal.landing')
                ->with('error', 'Session expired. Please try again.');
        }

        // Get video completion record
        $completion = VideoCompletion::findOrFail($completionId);

        // Security check: Ensure completion belongs to this device
        // Prevents one device from submitting words for another device's completion
        if ($completion->device_id !== $device->id) {
            return redirect()->route('portal.landing')
                ->with('error', 'Invalid session.');
        }

        // Get video that was watched
        // Load video relationship to ensure it's available
        $video = $completion->video;

        // Safety check: Video must exist
        if (! $video) {
            Log::error('Video not found for completion', [
                'completion_id' => $completion->id,
                'video_id' => $completion->video_id,
            ]);

            return redirect()->route('portal.landing')
                ->with('error', 'Video not found. Please try again.');
        }

        // Get child's entered words from form
        // Form sends words as array or comma-separated string
        // Example: ["adventure", "curious", "discover"] or "adventure, curious, discover"
        $wordsEnteredInput = $request->input('words', '');

        // Convert to array if it's a string
        // Handles both array input and comma-separated string input
        if (is_string($wordsEnteredInput)) {
            // Split by comma and trim each word
            $wordsEntered = array_map('trim', explode(',', $wordsEnteredInput));
            // Remove empty strings
            $wordsEntered = array_filter($wordsEntered);
        } else {
            $wordsEntered = is_array($wordsEnteredInput) ? $wordsEnteredInput : [];
        }

        // Get words that were actually displayed during video
        // These are stored in VideoWordDisplay table
        $wordsShown = $completion->getWordsShown();

        // Validate words using VideoWordService
        // This compares entered words with displayed words
        $validationResult = $this->videoWordService->validateWords($wordsShown, $wordsEntered);

        $wordsEnteredJson = json_encode(array_values($wordsEntered));

        if ($validationResult['passed_validation']) {
            $completion->update([
                'completed_at' => now(),
                'words_entered' => $wordsEnteredJson,
                'words_correct' => $validationResult['words_correct'],
                'passed_validation' => true,
            ]);

            $completion->refresh();

            try {
                $this->timeGrantingService->grantTimeFromVideo($device, $completion, ['client_ip' => $request->ip() ?: '']);

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

            session()->forget('video_completion_id');

            return redirect()->route('portal.video.result', $completion);
        }

        $failedCount = (int) $completion->word_guess_failed_count + 1;
        $completion->update([
            'completed_at' => $failedCount >= self::VIDEO_WORD_GUESS_MAX_FAILURES ? now() : null,
            'words_entered' => $wordsEnteredJson,
            'words_correct' => $validationResult['words_correct'],
            'passed_validation' => false,
            'word_guess_failed_count' => $failedCount,
        ]);

        $completion->refresh();

        if ($failedCount < self::VIDEO_WORD_GUESS_MAX_FAILURES) {
            $triesLeft = self::VIDEO_WORD_GUESS_MAX_FAILURES - $failedCount;
            session(['video_completion_id' => $completion->id]);

            return redirect()->route('portal.video.show', [
                'video' => $video->id,
                'mac' => $device->mac_address,
                'word_retry' => 1,
            ])->with('portal_video_word_retries_left', $triesLeft);
        }

        session()->forget('video_completion_id');

        return redirect()->route('portal.video.result', $completion);
    }

    /**
     * Show video result.
     *
     * Route: GET /portal/video/result/{completion}
     *
     * What it does:
     * 1. Gets the video completion record (contains validation results)
     * 2. Finds the device that watched the video
     * 3. Displays result page with different content based on pass/fail
     *
     * If Passed:
     * - Shows success message
     * - Displays correct word count
     * - Shows time granted (e.g., "You earned 15 minutes!")
     * - Auto-redirects after 3 seconds (JavaScript in view)
     *
     * If Failed:
     * - Shows failure message
     * - Displays correct words (so child can learn)
     * - Shows "Retry Video" button
     * - Child must watch entire video again with new random words
     *
     * Why show correct words on failure? Helps children learn the correct
     * words for next attempt. They still must watch the entire video again
     * with new random words, ensuring active learning.
     *
     * @param  VideoCompletion  $completion  The video completion record (found by ID from URL)
     * @return View|RedirectResponse Video result page or redirect if device not found
     */
    public function videoResult(VideoCompletion $completion): View|RedirectResponse
    {
        // Get device that watched the video
        $device = Device::find($completion->device_id);

        // Validation: Device must exist
        if (! $device) {
            return redirect()->route('portal.landing')
                ->with('error', 'Device not found.');
        }

        // Get video that was watched
        $video = $completion->video;

        // Get words that were displayed (for showing in error message)
        $wordsShown = $completion->getWordsShown();

        // If child passed validation, show success page with time granted
        if ($completion->passed_validation) {
            return view('portal.video-result', [
                'completion' => $completion,                                    // Video completion data
                'device' => $device,                                              // Device data
                'video' => $video,                                                // Video data
                'timeGranted' => $video->time_reward_minutes,                    // Minutes granted (e.g., 15)
                'wordsShown' => $wordsShown,                                     // Words that were displayed
            ]);
        }

        // If child failed validation, show failure page with retry option
        // timeGranted = 0 because no time was granted
        // Show correct words so child can learn for next attempt
        return view('portal.video-result', [
            'completion' => $completion,
            'device' => $device,
            'video' => $video,
            'timeGranted' => 0,  // No time granted for failed validation
            'wordsShown' => $wordsShown,  // Show correct words for learning
        ]);
    }
}
