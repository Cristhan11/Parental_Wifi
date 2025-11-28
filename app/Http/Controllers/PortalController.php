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
     * @param Request $request HTTP request (contains MAC address in ?mac= parameter)
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
        
        // If device not found, show landing page with error message
        // This happens if MAC address doesn't exist in database
        // Child will see friendly error message instead of crash
        if (!$device) {
            return view('portal.landing', [
                'device' => null,  // No device found
                'error' => 'Device not found. Please connect to the network.',
            ]);
        }
        
        // Get available quizzes for this device
        // ->quizzes() gets all quizzes assigned to this device (many-to-many relationship)
        // ->where('is_active', true) filters to only active quizzes (parents can deactivate)
        // ->get() executes query and returns collection of Quiz models
        $quizzes = $device->quizzes()
            ->where('is_active', true)
            ->get();
        
        // Get available videos for this device
        // ->videos() gets all videos assigned to this device (many-to-many relationship)
        // ->where('is_active', true) filters to only active videos
        // ->get() executes query and returns collection of Video models
        $videos = $device->videos()
            ->where('is_active', true)
            ->get();
        
        // Return landing page view with data
        // Passes device, quizzes, and videos to the Blade template
        // Template will display them in a user-friendly format
        return view('portal.landing', [
            'device' => $device,      // Device information (name, remaining time, etc.)
            'quizzes' => $quizzes,    // Collection of available quizzes
            'videos' => $videos,      // Collection of available videos
        ]);
    }

    /**
     * Get device from request (by MAC address or session).
     * 
     * This is a helper method used by all portal methods to identify which
     * child's device is accessing the portal.
     * 
     * How it works:
     * 1. Tries to get MAC address from URL query parameter (?mac=AA:BB:CC:DD:EE:FF)
     * 2. If not found, tries form input (POST data)
     * 3. If not found, tries session (stored from previous request)
     * 4. Looks up device in database by MAC address
     * 
     * Why MAC address? Each device has a unique MAC address (like a fingerprint).
     * This identifies which child's device is taking the quiz, even without login.
     * 
     * @param Request $request The HTTP request
     * @return Device|null The device if found, null if not found
     */
    protected function getDevice(Request $request): ?Device
    {
        // Try to get MAC address from three possible sources (in order):
        // 1. URL query parameter: ?mac=AA:BB:CC:DD:EE:FF
        // 2. Form input (POST data)
        // 3. Session (stored from previous request)
        // ?? is the "null coalescing operator" - uses next value if previous is null
        $macAddress = $request->query('mac')      // GET parameter
            ?? $request->input('mac')              // POST parameter
            ?? session('device_mac');             // Session storage

        // If no MAC address found, return null (device not found)
        if (!$macAddress) {
            return null;
        }

        // Look up device in database by MAC address
        // ->first() returns the first matching device or null if not found
        return Device::where('mac_address', $macAddress)->first();
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
     * @param Request $request HTTP request (contains MAC address)
     * @param Quiz $quiz The quiz to display (found by ID from URL)
     * @return View|RedirectResponse Quiz interface or redirect if validation fails
     */
    public function showQuiz(Request $request, Quiz $quiz): View|RedirectResponse
    {
        // Get device from MAC address in request
        $device = $this->getDevice($request);

        // Validation: Device must exist
        if (!$device) {
            return redirect()->route('portal.landing')
                ->with('error', 'Device not found. Please connect to the network.');
        }

        // Validation: Quiz must be active
        // Parents can deactivate quizzes to prevent children from taking them
        if (!$quiz->is_active) {
            return redirect()->route('portal.landing')
                ->with('error', 'This quiz is not available.');
        }

        // Validation: Device must be assigned to this quiz
        // ->quizzes gets all quizzes assigned to this device (relationship)
        // ->contains($quiz) checks if this specific quiz is in that list
        if (!$device->quizzes->contains($quiz)) {
            return redirect()->route('portal.landing')
                ->with('error', 'You do not have access to this quiz.');
        }

        // Extract questions from quiz's JSON structure
        // Questions are stored as: {questions: [{id: 1, question: "...", ...}]}
        // We extract the inner array: [{id: 1, question: "...", ...}]
        $questions = $quiz->questions['questions'] ?? [];
        
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
            ]
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
     * @param Request $request HTTP request containing submitted answers
     * @return RedirectResponse Redirects to quiz result page
     */
    public function submitQuiz(Request $request): RedirectResponse
    {
        // Get device and quiz attempt data
        $device = $this->getDevice($request);
        $quizAttemptData = session('quiz_attempt');  // Data stored in showQuiz()

        // Validation: Both device and session data must exist
        if (!$device || !$quizAttemptData) {
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
                
                $submittedLetter = strtolower(trim($submittedAnswer));  // "a" or "b" or "c" or "d"
                $options = $question['options'] ?? [];  // ["2", "3", "4", "5"]
                
                // Convert letter to array index: 'a' = 0, 'b' = 1, 'c' = 2, 'd' = 3
                // ord('a') = 97, ord('b') = 98, so ord('b') - ord('a') = 1
                $submittedIndex = ord($submittedLetter) - ord('a');
                
                // Get the option value the child selected
                if (isset($options[$submittedIndex])) {
                    $submittedOptionValue = strtolower(trim($options[$submittedIndex]));  // e.g., "4"
                    $correctAnswerValue = strtolower(trim($correctAnswer));  // e.g., "4"
                    $isCorrect = $submittedOptionValue === $correctAnswerValue;
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
        $passed = $quiz->isPassingScore($score);

        // Create quiz attempt record in database
        // This saves the attempt for history/analytics (parent can see results later)
        $attempt = QuizAttempt::create([
            'device_id' => $device->id,           // Which device took the quiz
            'quiz_id' => $quiz->id,               // Which quiz was taken
            'answers' => ['answers' => $submittedAnswers],  // Child's answers (JSON)
            'score' => $score,                    // Calculated score (0-100)
            'passed' => $passed,                  // Boolean: true if passed
            'completed_at' => now(),              // When quiz was finished
        ]);

        // Clear quiz attempt from session (no longer needed)
        session()->forget('quiz_attempt');

        // If child passed, grant additional internet time
        if ($passed) {
            try {
                // TimeGrantingService adds time to device
                // Example: If time_reward_minutes is 15, device gets 15 more minutes
                $this->timeGrantingService->grantTimeFromQuiz($device, $attempt);
            } catch (\Exception $e) {
                // Log error but don't fail the request
                // Child still sees result, but time grant might have failed
                Log::error('Failed to grant time after quiz: ' . $e->getMessage());
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
     * @param QuizAttempt $attempt The quiz attempt record (found by ID from URL)
     * @return View|RedirectResponse Quiz result page or redirect if device not found
     */
    public function quizResult(QuizAttempt $attempt): View|RedirectResponse
    {
        // Get device that took the quiz
        $device = Device::find($attempt->device_id);
        
        // Validation: Device must exist
        if (!$device) {
            return redirect()->route('portal.landing')
                ->with('error', 'Device not found.');
        }

        // If child passed, show success page with time granted
        if ($attempt->passed) {
            return view('portal.quiz-result', [
                'attempt' => $attempt,                                    // Quiz attempt data
                'device' => $device,                                      // Device data
                'timeGranted' => $attempt->quiz->time_reward_minutes,    // Minutes granted (e.g., 15)
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
     * @param Request $request HTTP request (contains MAC address)
     * @param Video $video The video to display (found by ID from URL)
     * @return View|RedirectResponse Video player interface or redirect if validation fails
     */
    public function showVideo(Request $request, Video $video): View|RedirectResponse
    {
        // Get device from MAC address in request
        $device = $this->getDevice($request);

        // Validation: Device must exist
        if (!$device) {
            return redirect()->route('portal.landing')
                ->with('error', 'Device not found. Please connect to the network.');
        }

        // Validation: Video must be active
        // Parents can deactivate videos to prevent children from watching them
        if (!$video->is_active) {
            return redirect()->route('portal.landing')
                ->with('error', 'This video is not available.');
        }

        // Validation: Device must be assigned to this video
        // ->videos gets all videos assigned to this device (relationship)
        // ->contains($video) checks if this specific video is in that list
        if (!$device->videos->contains($video)) {
            return redirect()->route('portal.landing')
                ->with('error', 'You do not have access to this video.');
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
        ]);

        // Handle dictionary words if enabled
        $wordsData = [];
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

            // Store word displays in database
            // This creates records in VideoWordDisplay table for validation
            foreach ($words as $index => $word) {
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
     * 4. Validates entered words against displayed words (case-insensitive, trimmed)
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
     * @param Request $request HTTP request containing entered words
     * @return RedirectResponse Redirects to video result page
     */
    public function submitVideoWords(Request $request): RedirectResponse
    {
        // Get device from MAC address in request
        $device = $this->getDevice($request);

        // Get video completion ID from session (stored in showVideo())
        $completionId = session('video_completion_id');

        // Validation: Both device and completion must exist
        if (!$device || !$completionId) {
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
        if (!$video) {
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

        // Update completion record with validation results
        $completion->update([
            'completed_at' => now(),  // Mark video as completed
            'words_entered' => json_encode($wordsEntered),  // Store entered words as JSON
            'words_correct' => $validationResult['words_correct'],  // Number of correct words
            'passed_validation' => $validationResult['passed_validation'],  // Pass/fail status
        ]);

        // Refresh completion from database to ensure all updated fields are current
        // This is important because TimeGrantingService checks passed_validation and completed_at
        $completion->refresh();

        // If child passed validation, grant additional internet time
        if ($validationResult['passed_validation']) {
            try {
                // TimeGrantingService adds time to device
                // Example: If time_reward_minutes is 15, device gets 15 more minutes
                // Pass fresh completion to ensure all fields are up-to-date
                $this->timeGrantingService->grantTimeFromVideo($device, $completion);
                
                Log::info('Time granted successfully after video completion', [
                    'device_id' => $device->id,
                    'video_completion_id' => $completion->id,
                    'time_granted' => $video->time_reward_minutes,
                ]);
            } catch (\Exception $e) {
                // Log error but don't fail the request
                // Child still sees result, but time grant might have failed
                Log::error('Failed to grant time after video', [
                    'device_id' => $device->id,
                    'video_completion_id' => $completion->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Clear completion ID from session (no longer needed)
        session()->forget('video_completion_id');

        // Redirect to results page
        // Child will see pass/fail status and time granted (if passed)
        // Route model binding will automatically resolve VideoCompletion from ID
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
     * @param VideoCompletion $completion The video completion record (found by ID from URL)
     * @return View|RedirectResponse Video result page or redirect if device not found
     */
    public function videoResult(VideoCompletion $completion): View|RedirectResponse
    {
        // Get device that watched the video
        $device = Device::find($completion->device_id);
        
        // Validation: Device must exist
        if (!$device) {
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

