# Quiz System - Complete Guide for Beginners

## Overview

The Quiz System allows parents to create educational quizzes for their children. When a child's internet time expires, they are redirected to a captive portal where they can take quizzes to earn additional internet time. This document explains how the system works in simple terms.

---

## How It Works: The Big Picture

Think of it like a **reward system**:
1. **Parent creates quizzes** → Stores questions and answers in the database
2. **Child's time expires** → Device gets blocked from internet
3. **Child takes quiz** → Answers questions on the portal
4. **System checks answers** → Calculates score (percentage correct)
5. **If child passes** → Gets additional internet time automatically
6. **If child fails** → Can retry the quiz (answers are not shown to prevent cheating)

---

## Part 1: QuizController (Parent Dashboard)

**Location:** `app/Http/Controllers/QuizController.php`

**What it does:** Handles everything parents need to manage quizzes.

### Key Methods Explained

#### 1. `index()` - Show All Quizzes
**What it does:** Displays a list of all quizzes the parent has created.

**How it works:**
```php
// Get all quizzes for the logged-in parent
$quizzes = Auth::user()->quizzes()->get();
// Show them in a table
return view('quizzes.index', compact('quizzes'));
```

**What parent sees:** A table with quiz titles, question counts, passing scores, and action buttons (Edit, Delete).

---

#### 2. `create()` - Show Create Form
**What it does:** Shows a form where parents can create a new quiz.

**How it works:**
- Displays an empty form
- Parent fills in: title, description, passing score, time reward
- Parent adds questions one by one (multiple choice, fill-in-blank, or true/false)

---

#### 3. `store()` - Save New Quiz
**What it does:** Saves the quiz to the database after parent submits the form.

**How it works:**
```php
// 1. Validate the form data (using StoreQuizRequest)
$validated = $request->validated();

// 2. Format questions with IDs
$questions = [];
foreach ($validated['questions'] as $index => $question) {
    $questions[] = [
        'id' => $index + 1,
        'question' => $question['question'],
        'type' => $question['type'],
        'options' => $question['options'] ?? [],
        'correct_answer' => $question['correct_answer'],
    ];
}

// 3. Save to database
Quiz::create([
    'user_id' => Auth::id(),  // Link to parent
    'title' => $validated['title'],
    'questions' => ['questions' => $questions],  // Store as JSON
    'passing_score' => $validated['passing_score'],
    'time_reward_minutes' => $validated['time_reward_minutes'],
]);
```

**Important:** Questions are stored as JSON (JavaScript Object Notation) - a way to store structured data in a text format. Example:
```json
{
  "questions": [
    {
      "id": 1,
      "question": "What is 2+2?",
      "type": "multiple_choice",
      "options": ["2", "3", "4", "5"],
      "correct_answer": "4"
    }
  ]
}
```

---

#### 4. `edit()` and `update()` - Modify Existing Quiz
**What it does:** Allows parents to change quiz details or questions.

**How it works:**
- `edit()` loads the quiz and shows a pre-filled form
- `update()` saves the changes (same process as `store()`)

**Security:** Checks that the parent owns the quiz before allowing edits.

---

#### 5. `destroy()` - Delete Quiz
**What it does:** Removes a quiz from the database.

**Safety check:** Prevents deletion if children have already attempted the quiz (to preserve history).

---

#### 6. `import()` and `processImport()` - Excel Import
**What it does:** Allows parents to upload an Excel file with quiz data instead of typing everything manually.

**How it works:**
1. Parent downloads a template Excel file
2. Parent fills in quiz data (title, questions, answers) in Excel
3. Parent uploads the file
4. System reads the Excel file and creates the quiz automatically

**Why useful:** Saves time when creating many quizzes or questions.

---

## Part 2: PortalController (Child Quiz Interface)

**Location:** `app/Http/Controllers/PortalController.php`

**What it does:** Handles the quiz-taking experience for children.

### Key Methods Explained

#### 1. `showQuiz()` - Display Quiz to Child
**What it does:** Shows the quiz questions one at a time for the child to answer.

**How it works:**
```php
// 1. Get device from MAC address (identifies which child's device)
$device = $this->getDevice($request);

// 2. Check if quiz is active and assigned to this device
if (!$quiz->is_active || !$device->quizzes->contains($quiz)) {
    return redirect()->with('error', 'Quiz not available');
}

// 3. Store quiz attempt in session (temporary storage)
session([
    'quiz_attempt' => [
        'quiz_id' => $quiz->id,
        'device_id' => $device->id,
        'questions' => $questions,
        'answers' => [],  // Will be filled as child answers
        'current_question' => 0,
    ]
]);

// 4. Show quiz interface
return view('portal.quiz', compact('quiz', 'device', 'questions'));
```

**What child sees:** 
- Question card with question text
- Answer options (radio buttons for multiple choice, text input for fill-in-blank)
- Timer showing time remaining
- Navigation buttons (Previous, Next, Submit)

---

#### 2. `submitQuiz()` - Process Answers and Calculate Score
**What it does:** This is the **most important method** - it checks if the child passed and grants time.

**How it works step-by-step:**

**Step 1: Get submitted answers**
```php
$submittedAnswers = $request->input('answers', []);
// Example: ['a', 'Paris', 'True'] - one answer per question
```

**Step 2: Compare each answer with correct answer**
```php
foreach ($questions as $index => $question) {
    $submittedAnswer = $submittedAnswers[$index];
    $correctAnswer = $question['correct_answer'];
    
    // Check if they match (handles different question types)
    if ($question['type'] === 'multiple_choice') {
        // Convert letter (a, b, c, d) to option value and compare
        $submittedOption = $options[$submittedIndex];
        $isCorrect = ($submittedOption === $correctAnswer);
    } else {
        // For fill_blank and true_false, compare directly
        $isCorrect = (strtolower($submittedAnswer) === strtolower($correctAnswer));
    }
    
    if ($isCorrect) {
        $correctCount++;
    }
}
```

**Step 3: Calculate score (percentage)**
```php
$score = ($correctCount / $totalQuestions) * 100;
// Example: 3 correct out of 5 = 60%
```

**Step 4: Check if passing**
```php
$passed = $quiz->isPassingScore($score);
// Example: If passing_score is 70% and score is 60%, $passed = false
```

**Step 5: Save attempt to database**
```php
$attempt = QuizAttempt::create([
    'device_id' => $device->id,
    'quiz_id' => $quiz->id,
    'answers' => $submittedAnswers,
    'score' => $score,
    'passed' => $passed,
    'completed_at' => now(),
]);
```

**Step 6: Grant time if passed**
```php
if ($passed) {
    // This calls TimeGrantingService to add time to device
    $this->timeGrantingService->grantTimeFromQuizAttempt($device, $attempt);
    // Device's remaining_time_minutes increases by time_reward_minutes
}
```

**Important:** The score is saved, but **correct answers are NOT shown** to the child. This allows them to retake the quiz without knowing the answers.

---

#### 3. `quizResult()` - Show Results Page
**What it does:** Displays the quiz result (pass or fail) to the child.

**If passed:**
- Shows success message
- Displays score (e.g., "85%")
- Shows time granted (e.g., "You earned 15 minutes!")
- **Automatically redirects after 3 seconds** to continue browsing

**If failed:**
- Shows failure message
- Displays score and required score
- Offers "Retry Quiz" button
- Does NOT show correct answers (to allow fair retry)

---

## Question Types Explained

### 1. Multiple Choice
**How it works:**
- Parent creates 4 options (A, B, C, D)
- Parent selects which option is correct
- Child sees all 4 options and selects one
- System compares: child's selected option value === correct option value

**Example:**
- Question: "What is 2+2?"
- Options: A) 2, B) 3, C) 4, D) 5
- Correct: C) 4
- Child selects "C" → System checks if option C's value ("4") matches correct answer ("4") → ✅ Correct

---

### 2. Fill in the Blank
**How it works:**
- Parent writes a question with a blank
- Parent enters the correct answer text
- Child types their answer
- System compares: child's text (lowercase, trimmed) === correct text (lowercase, trimmed)

**Example:**
- Question: "The capital of France is ___."
- Correct: "Paris"
- Child types "paris" → System converts to lowercase and compares → ✅ Correct

---

### 3. True/False
**How it works:**
- Parent writes a statement
- Parent selects True or False as correct
- Child selects True or False
- System compares directly

**Example:**
- Question: "The sky is blue."
- Correct: True
- Child selects "True" → ✅ Correct

---

## Data Flow: Complete Example

Let's trace what happens when a child takes a quiz:

### Step 1: Parent Creates Quiz
```
Parent fills form → QuizController::store() → Database
```
**Database stores:**
- Quiz ID: 1
- Title: "Math Quiz"
- Questions: [{"id": 1, "question": "2+2?", "type": "multiple_choice", "options": ["2","3","4","5"], "correct_answer": "4"}]
- Passing Score: 70%
- Time Reward: 15 minutes

---

### Step 2: Child's Time Expires
```
TimeTrackingService detects expiration → Device blocked → Redirect to portal
```

---

### Step 3: Child Takes Quiz
```
Child clicks "Take Quiz" → PortalController::showQuiz() → Shows question interface
```

**Session stores (temporary):**
- quiz_id: 1
- device_id: 5
- questions: [question data]
- answers: [] (empty, will be filled)

---

### Step 4: Child Submits Answers
```
Child answers all questions → Clicks "Submit" → PortalController::submitQuiz()
```

**Process:**
1. Get answers: ["c", "Paris", "True"]
2. Compare with correct: ["4", "Paris", "True"]
3. Calculate: 3 correct / 3 total = 100%
4. Check passing: 100% >= 70% → ✅ Passed
5. Save attempt to database
6. Grant 15 minutes to device

**Database stores:**
- QuizAttempt ID: 10
- device_id: 5
- quiz_id: 1
- score: 100
- passed: true
- answers: ["c", "Paris", "True"]

**Device updated:**
- remaining_time_minutes: 15 (was 0, now 15)
- total_time_allocated: 30 (was 15, now 30)

---

### Step 5: Show Result
```
PortalController::quizResult() → Shows "Congratulations! You passed! 100%"
→ Auto-redirects after 3 seconds → Child can browse internet again
```

---

## Security Features

1. **Authorization:** Parents can only edit/delete their own quizzes
2. **Device Verification:** Portal checks that device is assigned to quiz before allowing access
3. **Answer Protection:** Correct answers are never shown to children (allows fair retries)
4. **Session Management:** Quiz attempts stored in session to prevent tampering

---

## Key Files Summary

| File | Purpose |
|------|---------|
| `QuizController.php` | Parent dashboard - create/edit/delete quizzes |
| `PortalController.php` | Child interface - take quizzes, get results |
| `StoreQuizRequest.php` | Validates quiz creation form |
| `UpdateQuizRequest.php` | Validates quiz update form |
| `ImportQuizRequest.php` | Validates Excel import file |
| `QuizImportService.php` | Reads Excel files and creates quizzes |
| `quizzes/index.blade.php` | Parent view - list all quizzes |
| `quizzes/create.blade.php` | Parent view - create new quiz form |
| `quizzes/edit.blade.php` | Parent view - edit existing quiz |
| `quizzes/import.blade.php` | Parent view - Excel import interface |
| `portal/quiz.blade.php` | Child view - quiz taking interface |
| `portal/quiz-result.blade.php` | Child view - quiz results page |

---

## Common Questions

**Q: Why store questions as JSON?**
A: JSON allows flexible question structures (different types, varying number of options) without needing separate database tables for each question.

**Q: Why use sessions for quiz attempts?**
A: Sessions store temporary data (current question, answers so far) that doesn't need to be in the database until the quiz is completed.

**Q: What happens if child closes browser during quiz?**
A: Session is lost, quiz attempt is not saved. Child can start over.

**Q: Can a child take the same quiz multiple times?**
A: Yes! Each attempt creates a new QuizAttempt record. This allows tracking improvement over time.

**Q: How is time granted?**
A: TimeGrantingService adds minutes to device's `remaining_time_minutes` field and creates a DeviceTimeGrant record for tracking.

---

## Next Steps

After understanding this system, you can:
1. Add more question types (short answer, matching, etc.)
2. Add quiz analytics (average scores, pass rates)
3. Add time limits per question
4. Add question randomization
5. Add quiz categories/tags

---

## Summary

The Quiz System is a **reward mechanism** that:
- **Parents create** educational content (quizzes)
- **Children complete** quizzes when time expires
- **System validates** answers and calculates scores
- **Time is granted** automatically if child passes
- **No cheating** - answers hidden to allow fair retries

This creates a **win-win situation**: Children learn while earning internet time, and parents can control and monitor their child's educational progress.

