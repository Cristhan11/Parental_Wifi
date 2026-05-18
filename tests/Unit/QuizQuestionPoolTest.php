<?php

namespace Tests\Unit;

use App\Models\Quiz;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuizQuestionPoolTest extends TestCase
{
    #[Test]
    public function questions_per_child_attempt_defaults_to_fifteen_and_respects_pool(): void
    {
        $quiz = new Quiz([
            'question_count' => 15,
            'questions' => ['questions' => array_fill(0, 40, ['question' => 'Q'])],
        ]);

        $this->assertSame(40, $quiz->totalQuestionsInPool());
        $this->assertSame(15, $quiz->questionsPerChildAttempt());

        $quiz->question_count = 25;
        $this->assertSame(25, $quiz->questionsPerChildAttempt());
    }
}
