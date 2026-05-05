<?php

namespace App\Support;

/**
 * School levels for quizzes and question bank (parent-facing labels only).
 * No age-based filtering — any quiz can be assigned to any child device.
 */
final class QuizSchoolLevel
{
    public const KINDERGARTEN = 'Kindergarten';

    public const ELEMENTARY = 'Elementary';

    public const HIGH_SCHOOL = 'High School';

    public const SENIOR_HIGH_SCHOOL = 'Senior High School';

    /** @return list<string> */
    public static function levels(): array
    {
        return [
            self::KINDERGARTEN,
            self::ELEMENTARY,
            self::HIGH_SCHOOL,
            self::SENIOR_HIGH_SCHOOL,
        ];
    }
}
