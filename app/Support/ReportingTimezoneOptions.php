<?php

namespace App\Support;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Builds grouped IANA timezone options for the reporting preferences UI ({@see resources/views/reports/index.blade.php}).
 *
 * Why not hard-code a short list: parents abroad may need their local zone; we still surface Philippines first via `$recommended`.
 */
final class ReportingTimezoneOptions
{
    /**
     * @return array<string, array<string, string>> Group label => [ identifier => display label ]
     */
    public static function grouped(): array
    {
        $recommended = [
            'Asia/Manila' => 'Philippines — Asia/Manila (UTC+8)',
            'UTC' => 'UTC — Coordinated Universal Time',
        ];

        $recommendedKeys = array_flip(array_keys($recommended));

        $byRegion = [];
        foreach (timezone_identifiers_list() as $id) {
            if (isset($recommendedKeys[$id])) {
                continue;
            }
            $region = self::regionKey($id);
            if (! isset($byRegion[$region])) {
                $byRegion[$region] = [];
            }
            $byRegion[$region][$id] = self::formatLabel($id);
        }

        ksort($byRegion);

        foreach ($byRegion as $region => $options) {
            asort($byRegion[$region]);
        }

        return ['Recommended' => $recommended] + $byRegion;
    }

    /**
     * Flat map of all value => label (for “ensure current value exists in list”).
     *
     * @return array<string, string>
     */
    public static function flat(): array
    {
        $flat = [];
        foreach (self::grouped() as $options) {
            foreach ($options as $id => $label) {
                $flat[$id] = $label;
            }
        }

        return $flat;
    }

    private static function regionKey(string $id): string
    {
        if (! str_contains($id, '/')) {
            return $id;
        }

        return explode('/', $id, 2)[0];
    }

    private static function formatLabel(string $id): string
    {
        $human = str_replace('_', ' ', $id);
        $offset = self::offsetString($id);

        return $offset !== '' ? "{$human} ({$offset})" : $human;
    }

    private static function offsetString(string $id): string
    {
        try {
            $dt = new DateTime('now', new DateTimeZone($id));

            return 'UTC'.$dt->format('P');
        } catch (Exception) {
            return '';
        }
    }
}
