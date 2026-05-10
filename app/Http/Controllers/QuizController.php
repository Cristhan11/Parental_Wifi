<?php

/**
 * QuizController - Parent Dashboard Quiz Management
 *
 * This controller handles all quiz-related operations for parents in the dashboard.
 * Parents use this to create, edit, delete, and import quizzes that their children will take.
 *
 * How it works:
 * - Parents log in and access /quizzes
 * - They can create quizzes with multiple questions
 * - Questions are stored as JSON in the database
 * - Parents can also import quizzes from Excel files
 *
 * Security: Only authenticated parents can access these routes, and they can only
 * manage quizzes they created (checked via user_id).
 */

namespace App\Http\Controllers;

use App\Http\Requests\ImportQuizRequest;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Models\User;
use App\Services\QuestionBankExcelService;
use App\Support\QuestionBankExportUiState;
use App\Support\QuizSchoolLevel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuizController extends Controller
{
    /**
     * QuizImportService - Handles Excel file import functionality
     *
     * This service is injected via dependency injection. It's used to:
     * - Read Excel files and extract quiz data
     * - Generate Excel templates for download
     *
     * Why use a service? Separates business logic from controller, making code
     * more organized and testable.
     */
    protected QuestionBankExcelService $questionBankExcelService;

    /**
     * Constructor - Called automatically when controller is created
     *
     * Laravel's dependency injection automatically provides QuizImportService.
     * This is called "dependency injection" - Laravel creates the service for us.
     */
    public function __construct(QuestionBankExcelService $questionBankExcelService)
    {
        $this->questionBankExcelService = $questionBankExcelService;
    }

    /**
     * Display a listing of quizzes for the authenticated parent.
     *
     * Route: GET /quizzes
     *
     * What it does:
     * 1. Gets the currently logged-in parent user
     * 2. Fetches all quizzes created by this parent
     * 3. Counts how many times each quiz has been attempted (for statistics)
     * 4. Orders them by newest first (latest())
     * 5. Displays them in a table view
     *
     * Why withCount('attempts')? This efficiently counts quiz attempts without
     * loading all attempt records, which is faster for large datasets.
     *
     * @return View The quizzes index page showing all quizzes in a table
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $randomModeQuiz = $this->getOrCreateRandomModeQuiz($user);
        $randomModeQuiz->load('devices');
        $randomModeDeviceIds = $randomModeQuiz->devices()
            ->where('devices.role', 'child')
            ->pluck('devices.id')
            ->all();
        $assignableDevices = $this->quizAssignableDevices();

        // Build filter options from this parent's own quizzes.
        $filterLevels = $user->quizzes()
            ->where('title', '!=', Quiz::RANDOM_MODE_SETTINGS_TITLE)
            ->whereNotNull('level')
            ->distinct()
            ->orderByRaw("CASE level WHEN 'Kindergarten' THEN 1 WHEN 'Elementary' THEN 2 WHEN 'High School' THEN 3 WHEN 'Senior High School' THEN 4 ELSE 5 END")
            ->pluck('level');
        $filterSubjects = $user->quizzes()
            ->where('title', '!=', Quiz::RANDOM_MODE_SETTINGS_TITLE)
            ->whereNotNull('subject')
            ->distinct()
            ->orderByRaw("CASE 
                WHEN LOWER(subject) = 'math' THEN 1
                WHEN LOWER(subject) = 'science' THEN 2
                WHEN LOWER(subject) = 'english' THEN 3
                ELSE 4
            END")
            ->orderBy('subject')
            ->pluck('subject');

        // Search + filter query (parent-friendly list controls)
        $quizzes = $user->quizzes()
            ->where('title', '!=', Quiz::RANDOM_MODE_SETTINGS_TITLE)
            ->withCount('attempts')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->string('q'));
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('subject', 'like', "%{$term}%")
                        ->orWhere('level', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('level'), fn ($query) => $query->where('level', $request->string('level')->toString()))
            ->when($request->filled('subject'), fn ($query) => $query->where('subject', $request->string('subject')->toString()))
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->string('status')->toString() === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->string('status')->toString() === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderByRaw("CASE 
                WHEN LOWER(subject) = 'math' THEN 1
                WHEN LOWER(subject) = 'science' THEN 2
                WHEN LOWER(subject) = 'english' THEN 3
                ELSE 4
            END")
            ->orderBy('subject')
            ->latest()
            ->get();

        return view('quizzes.index', compact(
            'quizzes',
            'filterLevels',
            'filterSubjects',
            'randomModeQuiz',
            'randomModeDeviceIds',
            'assignableDevices'
        ));
    }

    /**
     * Show the form for creating a new quiz.
     *
     * Route: GET /quizzes/create
     *
     * What it does:
     * - Displays an empty form where parents can create a new quiz
     * - Form includes fields for: title, description, passing score, time reward
     * - JavaScript dynamically adds/removes questions
     *
     * The form is handled by JavaScript to allow adding multiple questions
     * without page reloads (dynamic form building).
     *
     * @return View The quiz creation form
     */
    public function create(): View
    {
        // Quizzes run on the child portal — only assign to registered child-role devices
        $devices = $this->quizAssignableDevices();

        return view('quizzes.create', compact('devices'));
    }

    /**
     * Store a newly created quiz in storage.
     *
     * Route: POST /quizzes
     *
     * What it does:
     * 1. Validates the form data (via StoreQuizRequest)
     * 2. Formats questions with sequential IDs (1, 2, 3, ...)
     * 3. Stores quiz in database
     * 4. Redirects back to quiz list with success message
     *
     * Why format questions? Questions come from form as array [0, 1, 2, ...]
     * but we want them stored with IDs [1, 2, 3, ...] for consistency.
     *
     * Questions are stored as JSON in the database. JSON (JavaScript Object Notation)
     * is a text format for storing structured data. Example:
     * {
     *   "questions": [
     *     {"id": 1, "question": "What is 2+2?", "type": "multiple_choice", ...}
     *   ]
     * }
     *
     * @param  StoreQuizRequest  $request  Validated form data (title, questions, etc.)
     * @return RedirectResponse Redirects to quiz list with success message
     */
    public function store(StoreQuizRequest $request): RedirectResponse
    {
        // Get validated form data (StoreQuizRequest ensures all required fields exist)
        // This prevents invalid data from reaching the database
        $validated = $request->validated();
        $scoringMode = 'pass_score';
        $passingScore = (int) ($validated['passing_score'] ?? 70);
        $fixedReward = (int) ($validated['time_reward_minutes'] ?? 15);
        $minutesPerCorrect = 1;

        // Format questions with sequential IDs
        // Questions come from form as: [0 => question1, 1 => question2, ...]
        // We convert to: [id => 1, id => 2, ...] for better readability
        $questions = [];

        // Create quiz record in database
        // Quiz::create() automatically saves to database
        $quiz = Quiz::create([
            'user_id' => Auth::id(),  // Link quiz to current parent (who created it)
            'title' => $validated['title'],  // Quiz name (e.g., "Math Quiz")
            'description' => $validated['description'] ?? null,  // Optional description
            'level' => $validated['level'],
            'subject' => $validated['subject'],
            'question_count' => (int) ($validated['question_count'] ?? 10),
            'scoring_mode' => $scoringMode,
            'minutes_per_correct' => $minutesPerCorrect,
            'passing_score' => $passingScore,
            'time_reward_minutes' => $fixedReward,
            'max_passes_per_day' => $validated['max_passes_per_day'] ?? null,
            'retry_cooldown_minutes' => $validated['retry_cooldown_minutes'] ?? null,
            'questions' => ['questions' => $questions],  // Store as JSON: {questions: [...]}
            'is_active' => true,  // New quizzes are active by default
        ]);

        // Assign quiz to selected child devices only (ignore tampered/non-child IDs)
        $quiz->devices()->sync($this->sanitizedQuizDeviceIds($request));

        // Redirect to quiz list page with success message
        // ->with() stores a message in session that displays on next page
        return redirect()->route('quizzes.index')
            ->with('success', 'Quiz created successfully!');
    }

    /**
     * Show the form for editing the specified quiz.
     *
     * Route: GET /quizzes/{quiz}/edit
     *
     * What it does:
     * 1. Checks if parent owns this quiz (security check)
     * 2. Loads quiz data from database
     * 3. Displays edit form pre-filled with existing quiz data
     *
     * Security: Prevents parents from editing other parents' quizzes.
     * If someone tries to edit a quiz they don't own, they get a 403 Forbidden error.
     *
     * @param  Quiz  $quiz  The quiz to edit (Laravel automatically finds it by ID from URL)
     * @return View The quiz edit form with existing data
     */
    public function edit(Quiz $quiz): View
    {
        // Security check: Ensure user owns this quiz
        // Prevents unauthorized access to other parents' quizzes
        // $quiz->user_id is the parent who created it
        // Auth::id() is the currently logged-in parent
        if ($quiz->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');  // 403 = Forbidden
        }

        // Backward compatibility for early seeded/built-in quizzes that were created
        // with empty question JSON. Hydrate visible questions from question bank for edit UI.
        $existingQuestions = $quiz->questions['questions'] ?? [];
        if (empty($existingQuestions) && $quiz->level && $quiz->subject) {
            $questionCount = max(1, (int) ($quiz->question_count ?? 10));
            $generatedQuestions = QuestionBankItem::queryForFixedQuiz($quiz)
                ->inRandomOrder()
                ->limit($questionCount)
                ->get()
                ->values()
                ->map(function (QuestionBankItem $item, int $index): array {
                    $payload = $item->toPortalQuestionPayload($index);
                    if ($payload['type'] === 'multiple_choice') {
                        $correctMap = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
                        $letter = strtoupper((string) $item->correct_option);
                        if (in_array($letter, ['A', 'B', 'C', 'D'], true)) {
                            $idx = $correctMap[$letter];
                            $opts = $payload['options'];
                            $payload['correct_answer'] = $opts[$idx] ?? $opts[0];
                        }
                    }

                    return $payload;
                })
                ->all();

            $quiz->setAttribute('questions', ['questions' => $generatedQuestions]);
        }

        $devices = $this->quizAssignableDevices();

        // Pre-check only child devices (detach parent/guest from display; save will sync child subset only)
        $assignedDeviceIds = $quiz->devices()
            ->where('devices.role', 'child')
            ->pluck('devices.id')
            ->toArray();

        return view('quizzes.edit', compact('quiz', 'devices', 'assignedDeviceIds'));
    }

    /**
     * Update the specified quiz in storage.
     *
     * Route: PUT /quizzes/{quiz}
     *
     * What it does:
     * 1. Validates the updated form data
     * 2. Checks parent owns the quiz (security)
     * 3. Formats questions with IDs (same as store method)
     * 4. Updates quiz in database
     * 5. Redirects with success message
     *
     * Special handling for is_active checkbox:
     * - HTML checkboxes don't send a value when unchecked
     * - We use a hidden input with value "0" to always get a value
     * - "0" = false, "1" = true (converted to boolean)
     *
     * @param  UpdateQuizRequest  $request  Validated form data
     * @param  Quiz  $quiz  The quiz to update (found by ID from URL)
     * @return RedirectResponse Redirects to quiz list with success message
     */
    public function update(UpdateQuizRequest $request, Quiz $quiz): RedirectResponse
    {
        // Security check: Ensure user owns this quiz
        // Same check as edit() method - prevents unauthorized updates
        if ($quiz->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Get validated form data
        $validated = $request->validated();
        $scoringMode = 'pass_score';
        $passingScore = (int) ($validated['passing_score'] ?? 70);
        $fixedReward = (int) ($validated['time_reward_minutes'] ?? 15);
        $minutesPerCorrect = 1;

        // Format questions with sequential IDs (same process as store method)
        $questions = [];
        foreach (($validated['questions'] ?? []) as $index => $question) {
            $questions[] = [
                'id' => $index + 1,
                'question' => $question['question'],
                'type' => $question['type'],
                'options' => $question['options'] ?? [],
                'correct_answer' => $question['correct_answer'],
            ];
        }

        // Update quiz record in database
        // $quiz->update() saves changes to existing record
        $quiz->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'level' => $validated['level'],
            'subject' => $validated['subject'],
            'question_count' => (int) ($validated['question_count'] ?? ($quiz->question_count ?: 10)),
            'scoring_mode' => $scoringMode,
            'minutes_per_correct' => $minutesPerCorrect,
            'passing_score' => $passingScore,
            'time_reward_minutes' => $fixedReward,
            'max_passes_per_day' => $validated['max_passes_per_day'] ?? null,
            'retry_cooldown_minutes' => $validated['retry_cooldown_minutes'] ?? null,
            'questions' => ['questions' => $questions],  // Update questions JSON
            'is_active' => $request->boolean('is_active'),
        ]);

        $quiz->devices()->sync($this->sanitizedQuizDeviceIds($request));

        return redirect()->route('quizzes.index')
            ->with('success', 'Quiz updated successfully!');
    }

    public function updateRandomModeSettings(Request $request): RedirectResponse
    {
        $assignable = $this->quizAssignableDevices();
        $rules = [
            'minutes_per_correct' => ['required', 'integer', 'min:1', 'max:60'],
            'retry_cooldown_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'max_passes_per_day' => ['nullable', 'integer', 'min:1', 'max:500'],
            'device_random_levels' => ['nullable', 'array'],
        ];
        foreach ($assignable as $device) {
            $rules['device_random_levels.'.$device->id] = ['nullable', 'array'];
            $rules['device_random_levels.'.$device->id.'.*'] = ['string', Rule::in(QuizSchoolLevel::levels())];
        }

        $validated = $request->validate($rules);

        $quiz = $this->getOrCreateRandomModeQuiz(Auth::user());
        $quiz->update([
            'minutes_per_correct' => (int) $validated['minutes_per_correct'],
            'retry_cooldown_minutes' => $validated['retry_cooldown_minutes'] ?? null,
            'max_passes_per_day' => $validated['max_passes_per_day'] ?? null,
            'is_active' => true,
        ]);

        $byDevice = $validated['device_random_levels'] ?? [];
        $allowedIds = $assignable->pluck('id')->all();
        $sync = [];
        foreach ($allowedIds as $deviceId) {
            $raw = $byDevice[$deviceId] ?? $byDevice[(string) $deviceId] ?? [];
            if (! is_array($raw)) {
                $raw = [];
            }
            $levels = array_values(array_unique(array_intersect(QuizSchoolLevel::levels(), $raw)));
            if ($levels !== []) {
                $sync[$deviceId] = ['random_bank_levels' => $levels];
            }
        }

        $quiz->devices()->sync($sync);

        return redirect()->route('quizzes.index')
            ->with('success', 'Random Quiz Mode settings updated.');
    }

    /**
     * Registered child devices for this parent (quizzes are not assigned to parent/guest roles).
     */
    protected function quizAssignableDevices(): Collection
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
    protected function sanitizedQuizDeviceIds(Request $request): array
    {
        $allowedIds = $this->quizAssignableDevices()->pluck('id')->all();
        $submitted = array_map('intval', (array) $request->input('devices', []));

        return array_values(array_intersect($allowedIds, $submitted));
    }

    /**
     * Remove the specified quiz from storage.
     *
     * Route: DELETE /quizzes/{quiz}
     *
     * What it does:
     * 1. Checks parent owns the quiz (security)
     * 2. Deletes quiz from database (attempt rows cascade via FK)
     *
     * @param  Quiz  $quiz  The quiz to delete
     * @return RedirectResponse Redirects to quiz list with message
     */
    public function destroy(Quiz $quiz): RedirectResponse
    {
        // Security check: Ensure user owns this quiz
        if ($quiz->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $attemptCount = $quiz->attempts()->count();
        if ($attemptCount > 0) {
            Log::info("Deleting quiz ID {$quiz->id} with {$attemptCount} attempt(s). User: ".Auth::id());
        }

        // Delete quiz from database
        // This permanently removes the quiz record
        $quiz->delete();

        return redirect()->route('quizzes.index')
            ->with('success', 'Quiz deleted successfully!');
    }

    /**
     * Show the Excel import form.
     *
     * Route: GET /quizzes/import
     *
     * What it does:
     * - Displays a form where parents can upload an Excel file
     * - Form includes file upload input and link to download template
     *
     * Why Excel import? Allows parents to create many quizzes quickly
     * by filling out a spreadsheet instead of using the web form.
     *
     * @return View The Excel import form
     */
    public function import(): View
    {
        $user = Auth::user();
        $state = QuestionBankExportUiState::forUser($user);
        $updateTargetQuizzes = $user->quizzes()
            ->where('title', '!=', Quiz::RANDOM_MODE_SETTINGS_TITLE)
            ->orderBy('subject')
            ->orderBy('level')
            ->orderBy('title')
            ->get(['id', 'title', 'level', 'subject']);

        return view('quizzes.import', array_merge($state, [
            'updateTargetQuizzes' => $updateTargetQuizzes,
        ]));
    }

    /**
     * Process the Excel import.
     *
     * Route: POST /quizzes/import
     *
     * What it does:
     * 1. Validates the uploaded Excel file (via ImportQuizRequest)
     * 2. Calls QuizImportService to read and parse the Excel file
     * 3. Creates quiz in database from Excel data
     * 4. Redirects with success or error message
     *
     * Excel Format Expected:
     * - Row 1: Headers (Quiz Title, Description, Passing Percentage, Time Reward, Question, Type, Options A-D, Correct Answer)
     * - Row 2+: Quiz metadata (first row) and questions (remaining rows)
     *
     * Error Handling: If import fails (invalid format, missing data, etc.),
     * catches the exception and shows error message to user.
     *
     * @param  ImportQuizRequest  $request  Validated file upload request
     * @return RedirectResponse Redirects to quiz list (success) or import form (error)
     */
    public function processImport(ImportQuizRequest $request): RedirectResponse
    {
        $userId = (int) Auth::id();
        $result = $this->questionBankExcelService->import(
            $request->file('excel_file'),
            $userId,
            $request->string('mode')->toString(),
            $request->integer('quiz_id') ?: null,
            $request->integer('confirm_replace_quiz_id') ?: null
        );

        if (! empty($result['errors'])) {
            return redirect()->route('quizzes.import')
                ->with('error', implode(' ', array_slice($result['errors'], 0, 5)));
        }

        if (! empty($result['pending_duplicate'])) {
            $pd = $result['pending_duplicate'];
            $relativePath = 'quiz-import-pending/'.$userId.'/'.$pd['token'].'.xlsx';
            Storage::disk('local')->put($relativePath, $request->file('excel_file')->getContent());
            Cache::put($this->importPendingCacheKey($userId, $pd['token']), [
                'path' => $relativePath,
                'mode' => $request->string('mode')->toString(),
                'quiz_id' => $request->integer('quiz_id') ?: null,
                'duplicate_quiz_id' => $pd['quiz_id'],
                'duplicate_title' => $pd['title'],
            ], now()->addMinutes(20));

            return redirect()
                ->route('quizzes.import.pending', ['token' => $pd['token']]);
        }

        return redirect()->route('quizzes.index')
            ->with('success', "Quiz import complete. Added: {$result['created']}, Updated: {$result['updated']}.");
    }

    public function importPending(string $token): RedirectResponse|View
    {
        $userId = (int) Auth::id();
        $payload = Cache::get($this->importPendingCacheKey($userId, $token));
        if (! is_array($payload)) {
            return redirect()->route('quizzes.import')
                ->with('error', 'That import confirmation link has expired. Please upload your file again.');
        }

        return view('quizzes.import-confirm', [
            'token' => $token,
            'duplicateQuizId' => (int) ($payload['duplicate_quiz_id'] ?? 0),
            'duplicateTitle' => (string) ($payload['duplicate_title'] ?? ''),
        ]);
    }

    public function processImportPending(\Illuminate\Http\Request $request, string $token): RedirectResponse
    {
        $request->validate([
            'choice' => ['required', 'in:replace,cancel'],
        ]);

        $userId = (int) Auth::id();
        $cacheKey = $this->importPendingCacheKey($userId, $token);
        $payload = Cache::pull($cacheKey);
        if (! is_array($payload)) {
            return redirect()->route('quizzes.import')
                ->with('error', 'That import confirmation link has expired. Please upload your file again.');
        }

        if ($request->string('choice')->toString() === 'cancel') {
            Storage::disk('local')->delete($payload['path']);

            return redirect()->route('quizzes.import')
                ->with('error', 'Import canceled. Change the Quiz title in your spreadsheet (the Quiz title row) and try again, or choose Replace next time.');
        }

        $fullPath = Storage::disk('local')->path($payload['path']);
        if (! is_file($fullPath)) {
            return redirect()->route('quizzes.import')
                ->with('error', 'The uploaded file is no longer available. Please upload again.');
        }

        $uploaded = new \Illuminate\Http\UploadedFile(
            $fullPath,
            'import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $result = $this->questionBankExcelService->import(
            $uploaded,
            $userId,
            (string) ($payload['mode'] ?? 'add_new'),
            isset($payload['quiz_id']) ? (int) $payload['quiz_id'] : null,
            (int) ($payload['duplicate_quiz_id'] ?? 0) ?: null
        );

        Storage::disk('local')->delete($payload['path']);

        if (! empty($result['errors'])) {
            return redirect()->route('quizzes.import')
                ->with('error', implode(' ', array_slice($result['errors'], 0, 5)));
        }

        return redirect()->route('quizzes.index')
            ->with('success', "Quiz import complete. Added: {$result['created']}, Updated: {$result['updated']}.");
    }

    protected function importPendingCacheKey(int $userId, string $token): string
    {
        return 'quiz_import_pending:'.$userId.':'.$token;
    }

    /**
     * Download Excel template.
     *
     * Route: GET /quizzes/template/download
     *
     * What it does:
     * - Generates an Excel file with the correct format
     * - Includes headers and example rows
     * - Downloads the file to user's computer
     *
     * Why template? Shows parents exactly how to format their Excel file
     * with headers and example data they can copy/modify.
     *
     * @return StreamedResponse Excel file download
     */
    public function downloadTemplate()
    {
        return $this->questionBankExcelService->template();
    }

    public function exportQuestionBank(Request $request): RedirectResponse|StreamedResponse
    {
        $validator = Validator::make($request->all(), [
            'export_level' => ['required', 'string', Rule::in(QuizSchoolLevel::levels())],
            'quiz_ids' => ['required', 'array', 'min:1'],
            'quiz_ids.*' => ['integer', Rule::exists('quizzes', 'id')->where('user_id', Auth::id())],
        ]);

        if ($validator->fails()) {
            return redirect()->route('quizzes.import')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $quizzes = Quiz::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $validated['quiz_ids'])
            ->get();

        foreach ($quizzes as $quiz) {
            if ($quiz->isRandomModeSettingsQuiz()) {
                return redirect()->route('quizzes.import')
                    ->withErrors(['quiz_ids' => 'This export only includes standard quizzes for a school level.'])
                    ->withInput();
            }
            if ($quiz->level !== $validated['export_level']) {
                return redirect()->route('quizzes.import')
                    ->withErrors(['quiz_ids' => 'Each selected quiz must use the same school level you chose above.'])
                    ->withInput();
            }
        }

        $subjects = array_values(array_unique($quizzes->pluck('subject')->filter()->map(fn ($s) => (string) $s)->all()));
        if ($subjects === []) {
            return redirect()->route('quizzes.import')
                ->withErrors(['quiz_ids' => 'Selected quizzes need a subject so we know what to export.'])
                ->withInput();
        }

        try {
            return $this->questionBankExcelService->exportForQuizzes(
                $quizzes,
                (int) Auth::id()
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('quizzes.import')
                ->with('error', $e->getMessage());
        }
    }

    protected function getOrCreateRandomModeQuiz(User $user): Quiz
    {
        return Quiz::firstOrCreate(
            [
                'user_id' => $user->id,
                'title' => Quiz::RANDOM_MODE_SETTINGS_TITLE,
            ],
            [
                'description' => 'Global random quiz mode settings for child devices.',
                'level' => null,
                'subject' => null,
                'question_count' => 10,
                'scoring_mode' => 'time_reward',
                'minutes_per_correct' => 1,
                'passing_score' => 0,
                'time_reward_minutes' => 1,
                'max_passes_per_day' => null,
                'retry_cooldown_minutes' => null,
                'questions' => ['questions' => []],
                'is_active' => true,
            ]
        );
    }
}
