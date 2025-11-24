# Portal Access Guide - Testing Child Quiz Interface

## Quick Access Instructions

### Step 1: Ensure Test Data is Loaded

If you haven't already, run the test data seeder:

```bash
php artisan db:seed --class=QuizTestDataSeeder
```

This creates:
- **Test Device** with MAC address: `AA:BB:CC:DD:EE:FF`
- **4 Active Quizzes** assigned to the device

### Step 2: Get a Quiz ID

You can find quiz IDs in two ways:

**Option A: From the Parent Dashboard**
1. Login as parent: `parent@test.com` / `password`
2. Go to `/quizzes` 
3. Click on any quiz to see its ID in the URL (e.g., `/quizzes/1/edit`)

**Option B: Check Database**
```bash
php artisan tinker
```
Then run:
```php
\App\Models\Quiz::where('is_active', true)->get(['id', 'title']);
```

### Step 3: Access the Portal

Use this URL format in your browser:

```
http://127.0.0.1:8000/portal/quiz/{QUIZ_ID}?mac=AA:BB:CC:DD:EE:FF
```

**Example URLs:**
- Quiz ID 1: `http://127.0.0.1:8000/portal/quiz/1?mac=AA:BB:CC:DD:EE:FF`
- Quiz ID 2: `http://127.0.0.1:8000/portal/quiz/2?mac=AA:BB:CC:DD:EE:FF`
- Quiz ID 3: `http://127.0.0.1:8000/portal/quiz/3?mac=AA:BB:CC:DD:EE:FF`
- Quiz ID 4: `http://127.0.0.1:8000/portal/quiz/4?mac=AA:BB:CC:DD:EE:FF`

## Test Data Details

### Test Device
- **MAC Address:** `AA:BB:CC:DD:EE:FF`
- **Name:** Test Device
- **Status:** Active

### Available Quizzes

1. **Math Quiz - Basic Addition** (Multiple Choice)
   - 4 questions
   - Passing score: 70%
   - Time reward: 15 minutes

2. **Geography Quiz - Capitals** (Fill-in-the-Blank)
   - 3 questions
   - Passing score: 60%
   - Time reward: 20 minutes

3. **Science Quiz - True or False** (True/False)
   - 3 questions
   - Passing score: 75%
   - Time reward: 10 minutes

4. **General Knowledge Quiz** (Mixed Types)
   - 5 questions (multiple choice, fill-in-the-blank, true/false)
   - Passing score: 80%
   - Time reward: 25 minutes

## What You'll See

### Quiz Interface Features:
- ✅ Yellow header with "QUIZ" label
- ✅ Timer showing time remaining (10:00 default)
- ✅ Back button
- ✅ Questions displayed one at a time
- ✅ Navigation between questions
- ✅ Submit button at the end

### Question Types:
- **Multiple Choice:** Radio buttons with yellow background
- **Fill-in-the-Blank:** Text input field
- **True/False:** Two radio button options

## Testing Different Scenarios

### Test Passing a Quiz:
1. Access a quiz URL
2. Answer all questions correctly
3. Submit the quiz
4. You should see:
   - Pass/fail status
   - Score percentage
   - Time granted message
   - Auto-redirect if passed

### Test Failing a Quiz:
1. Access a quiz URL
2. Answer questions incorrectly (below passing score)
3. Submit the quiz
4. You should see:
   - Fail status
   - Score percentage
   - No time granted

### Test Different Question Types:
- Try Quiz 1 (Multiple Choice)
- Try Quiz 2 (Fill-in-the-Blank)
- Try Quiz 3 (True/False)
- Try Quiz 4 (Mixed Types)

## Troubleshooting

### Error: "Device not found"
- Make sure you're using the correct MAC address: `AA:BB:CC:DD:EE:FF`
- Check that the device exists in the database

### Error: "You do not have access to this quiz"
- The device must be assigned to the quiz
- Run the seeder again: `php artisan db:seed --class=QuizTestDataSeeder`

### Error: "This quiz is not available"
- The quiz might be inactive
- Check `is_active` status in the database or parent dashboard

### Quiz Not Loading
- Make sure the quiz has questions
- Check that the quiz structure is correct (questions.questions array)

## Quick Test Script

To quickly test all quizzes, you can use this in your browser console or create bookmarks:

```javascript
// Test all 4 quizzes
const mac = 'AA:BB:CC:DD:EE:FF';
for (let i = 1; i <= 4; i++) {
    console.log(`Quiz ${i}: http://127.0.0.1:8000/portal/quiz/${i}?mac=${mac}`);
}
```

## Notes

- The portal is **public** (no authentication required)
- Access is controlled by MAC address and quiz assignment
- Session is used to track quiz progress
- Timer is set to 10 minutes by default (can be customized)

