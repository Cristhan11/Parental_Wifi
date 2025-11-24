<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\TimeGrantingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PortalController extends Controller
{
    protected TimeGrantingService $timeGrantingService;

    public function __construct(TimeGrantingService $timeGrantingService)
    {
        $this->timeGrantingService = $timeGrantingService;
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
                $this->timeGrantingService->grantTimeFromQuizAttempt($device, $attempt);
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

}

