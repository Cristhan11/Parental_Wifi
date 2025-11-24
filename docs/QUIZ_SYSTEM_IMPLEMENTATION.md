# Quiz System Implementation Summary (TODO #7)

## 📚 Table of Contents
1. [What is the Quiz System?](#what-is-the-quiz-system)
2. [How Does It Work?](#how-does-it-work)
3. [Files Created](#files-created)
4. [Logic Explanation](#logic-explanation)
5. [Data Flow](#data-flow)
6. [Key Features](#key-features)

---

## What is the Quiz System?

Think of the quiz system like a **homework assignment system** where:
- **Parents** create educational quizzes for their children
- **Children** take quizzes to earn additional internet time
- The system automatically checks answers and grants time if they pass

### The Big Picture

```
Parent Dashboard                    Child Portal
─────────────────                  ──────────────
1. Create Quiz          →         4. Child sees quiz
2. Add Questions        →         5. Child answers
3. Assign to Device     →         6. System checks answers
                                  7. If passed → Grant time! ⏰
```

---

## How Does It Work?

### Step-by-Step Flow

#### **Part 1: Parent Creates a Quiz**

1. **Parent logs in** to the dashboard (`/quizzes`)
2. **Clicks "Create New Quiz"**
3. **Fills out quiz details:**
   - Title: "Math Quiz - Addition"
   - Description: "Test your math skills"
   - Passing Score: 70% (child needs 70% correct to pass)
   - Time Reward: 15 minutes (if passed, child gets 15 more minutes of internet)
4. **Adds questions:**
   - Question text: "What is 2 + 2?"
   - Question type: Multiple Choice, Fill-in-the-Blank, or True/False
   - Options (for multiple choice): A) 2, B) 3, C) 4, D) 5
   - Correct answer: C) 4
5. **Saves the quiz** → Stored in database
6. **Assigns quiz to child's device** → Links quiz to device

#### **Part 2: Child Takes the Quiz**

1. **Child's time expires** → Device is blocked from internet
2. **Child opens browser** → Automatically redirected to portal
3. **Child selects "Take Quiz"** → Sees list of available quizzes
4. **Child clicks on a quiz** → Quiz interface loads
5. **Child answers questions** → One question at a time
6. **Child submits quiz** → System checks all answers
7. **System calculates score:**
   - If score ≥ passing score → **PASSED** ✅
   - If score < passing score → **FAILED** ❌
8. **If passed:**
   - Time is granted to device
   - Child sees success message
   - Device is unblocked
   - Child can browse internet again

---

## Files Created

### 🎯 Controllers (The Logic Handlers)

#### 1. `app/Http/Controllers/QuizController.php`
**Purpose:** Handles all quiz management for parents

**What it does:**
- Lists all quizzes (`index()`)
- Shows form to create new quiz (`create()`)
- Saves new quiz (`store()`)
- Shows form to edit quiz (`edit()`)
- Updates existing quiz (`update()`)
- Deletes quiz (`destroy()`)
- Shows import form (`import()`)
- Processes Excel file import (`processImport()`)
- Downloads Excel template (`downloadTemplate()`)

**Key Logic:**
```php
// When creating a quiz, questions are formatted with IDs
$questions = [];
foreach ($validated['questions'] as $index => $question) {
    $questions[] = [
        'id' => $index + 1,  // Question number
        'question' => $question['question'],
        'type' => $question['type'],
        'options' => $question['options'] ?? [],
        'correct_answer' => $question['correct_answer'],
    ];
}
```

#### 2. `app/Http/Controllers/PortalController.php` (Quiz Methods)
**Purpose:** Handles the child-facing quiz interface

**Methods:**
- `showQuiz()` - Displays quiz for child to take
- `submitQuiz()` - Processes quiz submission and calculates score
- `quizResult()` - Shows quiz result (pass/fail, score, time granted)

**Key Logic:**
```php
// Calculate score
$correctCount = 0;
foreach ($questions as $index => $question) {
    $userAnswer = $submittedAnswers[$index] ?? '';
    $correctAnswer = $question['correct_answer'];
    
    // Compare answers (case-insensitive for fill-in-the-blank)
    if (strtolower(trim($userAnswer)) === strtolower(trim($correctAnswer))) {
        $correctCount++;
    }
}

$score = ($correctCount / count($questions)) * 100;
$passed = $score >= $quiz->passing_score;
```

---

### 📋 Form Request Classes (Validation)

#### 1. `app/Http/Requests/StoreQuizRequest.php`
**Purpose:** Validates quiz creation form data

**Validates:**
- Title is required and max 255 characters
- Description is optional
- Passing score is 0-100
- Time reward is at least 1 minute
- At least 1 question required
- Each question has required fields
- Options required for multiple choice/true-false
- Correct answer is required

#### 2. `app/Http/Requests/UpdateQuizRequest.php`
**Purpose:** Same as StoreQuizRequest, but also validates `is_active` checkbox

#### 3. `app/Http/Requests/ImportQuizRequest.php`
**Purpose:** Validates Excel file upload

**Validates:**
- File is required
- File is Excel format (.xlsx or .xls)
- File is not too large

---

### 🔧 Services (Business Logic)

#### 1. `app/Services/QuizImportService.php`
**Purpose:** Handles importing quizzes from Excel files

**Two Main Methods:**

**`importFromExcel()`** - Reads Excel and creates quiz:
```php
// 1. Load Excel file
$spreadsheet = IOFactory::load($file->getRealPath());
$rows = $spreadsheet->getActiveSheet()->toArray();

// 2. Parse first row (quiz metadata)
$quizTitle = $rows[0][0];  // Column A
$description = $rows[0][1]; // Column B
$passingScore = $rows[0][2]; // Column C
$timeReward = $rows[0][3];   // Column D

// 3. Parse remaining rows (questions)
foreach ($rows as $row) {
    $question = [
        'question' => $row[4],  // Column E
        'type' => $row[5],      // Column F
        'options' => [$row[6], $row[7], $row[8], $row[9]], // Columns G-J
        'correct_answer' => $row[10], // Column K
    ];
}

// 4. Create quiz in database
Quiz::create([...]);
```

**`generateTemplate()`** - Creates downloadable Excel template:
```php
// Creates Excel file with:
// - Headers row
// - Example rows showing format
// - Styling (bold headers, yellow background)
// - Auto-sized columns
```

---

### 🎨 Views (User Interface)

#### Parent Dashboard Views

**1. `resources/views/quizzes/index.blade.php`**
- Shows table of all quizzes
- Displays: Title, Description, Passing Score, Time Reward
- Actions: Create, Import, Edit, Delete buttons
- Uses yellow/green color scheme

**2. `resources/views/quizzes/create.blade.php`**
- Form to create new quiz
- Dynamic question builder (JavaScript)
- Can add/remove questions
- Supports 3 question types:
  - Multiple Choice (4 options)
  - Fill-in-the-Blank (text input)
  - True/False (2 options)

**Key JavaScript Features:**
```javascript
// Add new question dynamically
function addQuestion() {
    // Creates HTML for question form
    // Increments question counter
    // Scrolls to new question
}

// Update question type (show/hide options)
function updateQuestionType(index, type) {
    if (type === 'fill_blank') {
        // Hide options, show text input
    } else {
        // Show options, show select dropdown
    }
}
```

**3. `resources/views/quizzes/edit.blade.php`**
- Same as create, but pre-fills with existing quiz data
- Loads questions from database
- Handles all question types correctly

**4. `resources/views/quizzes/import.blade.php`**
- File upload form
- Link to download Excel template
- Instructions for Excel format

#### Child Portal Views

**1. `resources/views/portal/quiz.blade.php`**
- Child-facing quiz interface
- Yellow header with timer
- Questions displayed one at a time
- Navigation between questions
- Submit button

**Features:**
- Timer countdown (10 minutes default)
- Question counter (Question 1 of 5)
- Different UI for each question type:
  - Multiple Choice: Radio buttons with yellow background
  - Fill-in-the-Blank: Text input field
  - True/False: Two radio buttons

**2. `resources/views/portal/quiz-result.blade.php`**
- Shows quiz result after submission
- Displays:
  - Pass/Fail status
  - Score percentage
  - Time granted (if passed)
- Auto-redirects if passed

---

### 🛣️ Routes

**In `routes/web.php`:**

```php
// Parent quiz management (requires authentication)
Route::middleware('auth')->group(function () {
    Route::resource('quizzes', QuizController::class);
    Route::get('/quizzes/import', [QuizController::class, 'import'])->name('quizzes.import');
    Route::post('/quizzes/import', [QuizController::class, 'processImport'])->name('quizzes.import.process');
    Route::get('/quizzes/template/download', [QuizController::class, 'downloadTemplate'])->name('quizzes.template.download');
});

// Portal routes (no auth, device-based)
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/quiz/{quiz}', [PortalController::class, 'showQuiz'])->name('quiz.show');
    Route::post('/quiz/submit', [PortalController::class, 'submitQuiz'])->name('quiz.submit');
    Route::get('/quiz/result/{attempt}', [PortalController::class, 'quizResult'])->name('quiz.result');
});
```

---

## Logic Explanation

### How Questions Are Stored

Questions are stored as **JSON** in the database. Here's the structure:

```json
{
  "questions": [
    {
      "id": 1,
      "question": "What is 2 + 2?",
      "type": "multiple_choice",
      "options": ["2", "3", "4", "5"],
      "correct_answer": "4"
    },
    {
      "id": 2,
      "question": "The capital of France is ___.",
      "type": "fill_blank",
      "correct_answer": "Paris"
    },
    {
      "id": 3,
      "question": "The sky is blue.",
      "type": "true_false",
      "options": ["True", "False"],
      "correct_answer": "True"
    }
  ]
}
```

**Why JSON?**
- Flexible: Can store different question types
- Simple: No need for separate tables
- Easy to query: Laravel automatically converts to array

### How Scoring Works

```php
// 1. Get all questions
$questions = $quiz->questions['questions'];

// 2. Get child's answers
$submittedAnswers = $request->input('answers', []);

// 3. Compare each answer
$correctCount = 0;
foreach ($questions as $index => $question) {
    $userAnswer = $submittedAnswers[$index] ?? '';
    $correctAnswer = $question['correct_answer'];
    
    // For fill-in-the-blank: case-insensitive comparison
    if ($question['type'] === 'fill_blank') {
        $isCorrect = strtolower(trim($userAnswer)) === strtolower(trim($correctAnswer));
    } 
    // For multiple choice/true-false: exact match
    else {
        $isCorrect = $userAnswer === $correctAnswer;
    }
    
    if ($isCorrect) {
        $correctCount++;
    }
}

// 4. Calculate percentage
$score = ($correctCount / count($questions)) * 100;

// 5. Check if passed
$passed = $score >= $quiz->passing_score;
```

### How Time Granting Works

```php
// After quiz is passed:
if ($passed) {
    // Grant time using TimeGrantingService
    $this->timeGrantingService->grantTime(
        device: $device,
        minutes: $quiz->time_reward_minutes,
        reason: "Quiz passed: {$quiz->title}",
        source: 'quiz',
        sourceId: $quizAttempt->id
    );
    
    // This updates:
    // - device.remaining_time_minutes (adds time)
    // - Creates record in device_time_grants table
}
```

### How Excel Import Works

**Excel Format:**
```
Row 1: Quiz Title | Description | Passing Score | Time Reward | Question | Type | Option A | Option B | Option C | Option D | Correct Answer
Row 2: Math Quiz | Basic math  | 70           | 15          | What is 2+2? | multiple_choice | 2 | 3 | 4 | 5 | 4
Row 3:           |             |              |             | Capital of France? | fill_blank |   |   |   |   | Paris
```

**Import Process:**
1. User uploads Excel file
2. `QuizImportService` reads file using PhpSpreadsheet
3. Parses first row → Quiz metadata
4. Parses remaining rows → Questions
5. Normalizes question types (e.g., "Multiple Choice" → "multiple_choice")
6. Creates quiz in database

---

## Data Flow

### Creating a Quiz (Parent)

```
1. Parent fills form
   ↓
2. Browser sends POST /quizzes
   ↓
3. StoreQuizRequest validates data
   ↓
4. QuizController@store processes data
   ↓
5. Questions formatted with IDs
   ↓
6. Quiz saved to database
   ↓
7. Redirect to quiz list
```

### Taking a Quiz (Child)

```
1. Child accesses /portal/quiz/1?mac=AA:BB:CC:DD:EE:FF
   ↓
2. PortalController@showQuiz:
   - Validates device exists
   - Checks quiz is active
   - Checks device is assigned to quiz
   - Stores quiz attempt in session
   ↓
3. quiz.blade.php displays quiz
   ↓
4. Child answers questions
   ↓
5. Child submits form
   ↓
6. PortalController@submitQuiz:
   - Gets answers from form
   - Compares with correct answers
   - Calculates score
   - Determines pass/fail
   - Creates QuizAttempt record
   - If passed: Grants time via TimeGrantingService
   ↓
7. Redirect to quiz-result.blade.php
   ↓
8. Shows result (pass/fail, score, time granted)
   ↓
9. If passed: Auto-redirect after 3 seconds
```

---

## Key Features

### ✅ Question Types Supported

1. **Multiple Choice**
   - 4 options (A, B, C, D)
   - Radio button selection
   - Correct answer stored as option value

2. **Fill-in-the-Blank**
   - Text input field
   - Case-insensitive comparison
   - No options needed

3. **True/False**
   - 2 options (True, False)
   - Radio button selection
   - Correct answer: "True" or "False"

### ✅ Parent Features

- Create quizzes with multiple questions
- Edit existing quizzes
- Delete quizzes
- Import quizzes from Excel
- Download Excel template
- Enable/disable quizzes (Active checkbox)
- Assign quizzes to devices

### ✅ Child Features

- View quiz questions one at a time
- Navigate between questions
- See timer countdown
- Submit quiz
- See results (score, pass/fail)
- Auto-redirect if passed

### ✅ Security Features

- Only parent can create/edit their own quizzes
- Device must be assigned to quiz to access it
- Quiz must be active to be taken
- Answers are validated server-side
- Session-based quiz attempt tracking

---

## Database Structure

### `quizzes` Table
- `id` - Unique identifier
- `user_id` - Parent who created it
- `title` - Quiz name
- `description` - Optional description
- `questions` - JSON array of questions
- `passing_score` - Percentage needed to pass (0-100)
- `time_reward_minutes` - Minutes granted if passed
- `is_active` - Enable/disable quiz
- `created_at`, `updated_at` - Timestamps

### `quiz_attempts` Table
- `id` - Unique identifier
- `device_id` - Device that took quiz
- `quiz_id` - Quiz that was attempted
- `answers` - JSON array of child's answers
- `score` - Calculated score (0-100)
- `passed` - Boolean (true if score ≥ passing_score)
- `completed_at` - When quiz was finished
- `created_at`, `updated_at` - Timestamps

### `device_quiz` Table (Pivot)
- `device_id` - Device
- `quiz_id` - Quiz
- Links devices to quizzes (many-to-many relationship)

---

## Common Issues & Solutions

### Issue: Questions not showing in edit form
**Solution:** Fixed JavaScript to properly load questions from JSON structure

### Issue: Fill-in-the-blank validation error
**Solution:** 
- Made options nullable for fill_blank type
- Added form submission handler to remove option inputs
- Updated validation rules

### Issue: Active checkbox not updating
**Solution:** 
- Added hidden input with value "0"
- Checkbox sends "1" when checked, "0" when unchecked
- Controller converts to boolean

### Issue: Duplicate variable declaration error
**Solution:** 
- Wrapped JavaScript in IIFE (Immediately Invoked Function Expression)
- Used `window.quizQuestionIndex` instead of `let questionIndex`
- Added check to prevent script running multiple times

---

## Testing

### Test Data Setup

Run the seeder to create test data:
```bash
php artisan db:seed --class=QuizTestDataSeeder
```

This creates:
- Test parent user: `parent@test.com` / `password`
- Test device: MAC `AA:BB:CC:DD:EE:FF`
- 4 sample quizzes (Math, Geography, Science, General Knowledge)

### Accessing the Portal

Use this URL format:
```
http://127.0.0.1:8000/portal/quiz/{QUIZ_ID}?mac=AA:BB:CC:DD:EE:FF
```

Example:
```
http://127.0.0.1:8000/portal/quiz/1?mac=AA:BB:CC:DD:EE:FF
```

---

## Summary

The quiz system allows parents to create educational quizzes that children can take to earn additional internet time. It consists of:

1. **Parent Dashboard** - Create, edit, import quizzes
2. **Child Portal** - Take quizzes, see results
3. **Automatic Scoring** - Compares answers, calculates score
4. **Time Granting** - Automatically grants time if quiz is passed
5. **Excel Import** - Bulk import quizzes from Excel files

The system is fully functional and ready for testing! 🎉

