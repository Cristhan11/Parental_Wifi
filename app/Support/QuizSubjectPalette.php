<?php

namespace App\Support;

/**
 * Dashboard row colors for quizzes, keyed by subject.
 *
 * Known subjects use fixed yellow-family shades; any other subject gets a stable
 * shade from the expansion palette (same name → same color across page loads).
 */
final class QuizSubjectPalette
{
    /** @var array<string, array{bg: string, text: string, muted: string, border: string}> */
    private const KNOWN = [
        'math' => [
            'bg' => '#FFDE15',
            'text' => '#1C1917',
            'muted' => '#44403C',
            'border' => '#EAB308',
        ],
        'science' => [
            'bg' => '#FEF3C7',
            'text' => '#78350F',
            'muted' => '#92400E',
            'border' => '#FCD34D',
        ],
        'english' => [
            'bg' => '#FFFBEB',
            'text' => '#422006',
            'muted' => '#713F12',
            'border' => '#FDE68A',
        ],
        'filipino' => [
            'bg' => '#FDE68A',
            'text' => '#713F12',
            'muted' => '#854D0E',
            'border' => '#FACC15',
        ],
        'sibika' => [
            'bg' => '#FEF9C3',
            'text' => '#713F12',
            'muted' => '#854D0E',
            'border' => '#FDE047',
        ],
        'aralin panlipunan' => [
            'bg' => '#FEF7CD',
            'text' => '#78350F',
            'muted' => '#92400E',
            'border' => '#FCD34D',
        ],
        'literature' => [
            'bg' => '#FFF4CC',
            'text' => '#713F12',
            'muted' => '#854D0E',
            'border' => '#FBBF24',
        ],
        'media and information literacy' => [
            'bg' => '#FEF08A',
            'text' => '#713F12',
            'muted' => '#854D0E',
            'border' => '#EAB308',
        ],
        'general mathematics' => [
            'bg' => '#FFF9DB',
            'text' => '#713F12',
            'muted' => '#854D0E',
            'border' => '#FACC15',
        ],
    ];

    /** @return array{bg: string, text: string, muted: string, border: string} */
    public static function forSubject(?string $subject): array
    {
        $key = self::normalizeKey($subject);

        if ($key === '') {
            return self::defaultStyle();
        }

        if (isset(self::KNOWN[$key])) {
            return self::KNOWN[$key];
        }

        foreach (self::KNOWN as $knownKey => $style) {
            if (str_contains($key, $knownKey) || str_contains($knownKey, $key)) {
                return $style;
            }
        }

        return self::paletteForUnknown($key);
    }

    public static function normalizeKey(?string $subject): string
    {
        $raw = strtolower(trim((string) $subject));
        if ($raw === '') {
            return '';
        }

        $raw = preg_replace('/\s+/', ' ', $raw) ?? $raw;

        return match ($raw) {
            'mathematics', 'matematika', 'maths' => 'math',
            'general math', 'gen math' => 'general mathematics',
            'sciences', 'sci' => 'science',
            'english language', 'inglish' => 'english',
            'filipino language', 'wika', 'filipino and english' => 'filipino',
            'sibika at kultura', 'sibika and filipino', 'sibika at kultura (sibika)' => 'sibika',
            'araling panlipunan', 'ap', 'social studies' => 'aralin panlipunan',
            '21st century literature', '21st-century literature' => 'literature',
            'media literacy', 'mil', 'media & information literacy' => 'media and information literacy',
            default => $raw,
        };
    }

    /** @return array{bg: string, text: string, muted: string, border: string} */
    private static function defaultStyle(): array
    {
        return [
            'bg' => '#FFFFCC',
            'text' => '#1C1917',
            'muted' => '#57534E',
            'border' => '#FDE68A',
        ];
    }

    /**
     * Additional yellow shades for subjects not in KNOWN (stable via crc32 index).
     *
     * @return list<array{bg: string, text: string, muted: string, border: string}>
     */
    private static function expansionPalette(): array
    {
        static $palette = null;

        if ($palette !== null) {
            return $palette;
        }

        $palette = [
            ['bg' => '#FFF9DB', 'text' => '#713F12', 'muted' => '#854D0E', 'border' => '#FDE047'],
            ['bg' => '#FEFCE8', 'text' => '#422006', 'muted' => '#713F12', 'border' => '#FEF08A'],
            ['bg' => '#FEF3C7', 'text' => '#78350F', 'muted' => '#92400E', 'border' => '#FCD34D'],
            ['bg' => '#FEF9C3', 'text' => '#713F12', 'muted' => '#854D0E', 'border' => '#FDE68A'],
            ['bg' => '#FFFBEB', 'text' => '#422006', 'muted' => '#713F12', 'border' => '#FDE68A'],
            ['bg' => '#FEF7CD', 'text' => '#78350F', 'muted' => '#92400E', 'border' => '#FCD34D'],
            ['bg' => '#FFF4CC', 'text' => '#713F12', 'muted' => '#854D0E', 'border' => '#FBBF24'],
            ['bg' => '#FEF08A', 'text' => '#713F12', 'muted' => '#854D0E', 'border' => '#EAB308'],
            ['bg' => '#FDE68A', 'text' => '#713F12', 'muted' => '#854D0E', 'border' => '#FACC15'],
            ['bg' => '#FFFBE6', 'text' => '#422006', 'muted' => '#713F12', 'border' => '#FEF08A'],
            ['bg' => '#FEF9C3', 'text' => '#78350F', 'muted' => '#92400E', 'border' => '#FDE047'],
            ['bg' => '#FFF9C4', 'text' => '#713F12', 'muted' => '#854D0E', 'border' => '#FACC15'],
        ];

        return $palette;
    }

    /** @return array{bg: string, text: string, muted: string, border: string} */
    private static function paletteForUnknown(string $key): array
    {
        $palette = self::expansionPalette();
        $index = abs(crc32($key)) % count($palette);

        return $palette[$index];
    }
}
