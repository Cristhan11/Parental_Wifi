<?php

namespace Tests\Unit;

use App\Support\QuizSubjectPalette;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuizSubjectPaletteTest extends TestCase
{
    #[Test]
    public function known_subjects_use_stable_palette_entries(): void
    {
        $math = QuizSubjectPalette::forSubject('Math');
        $mathAgain = QuizSubjectPalette::forSubject('MATHEMATICS');

        $this->assertSame($math, $mathAgain);
        $this->assertSame('#FFDE15', $math['bg']);
    }

    #[Test]
    public function unknown_subjects_get_stable_expansion_colors(): void
    {
        $first = QuizSubjectPalette::forSubject('Robotics');
        $second = QuizSubjectPalette::forSubject('Robotics');
        $other = QuizSubjectPalette::forSubject('Music');

        $this->assertSame($first, $second);
        $this->assertNotSame($first, $other);
    }
}
