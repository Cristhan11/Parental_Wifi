<?php

namespace App\Support;

use App\Models\Device;

/**
 * Short, specific sentences for device-related security audit rows.
 */
final class DeviceAuditSummary
{
    /**
     * @param  array<string, mixed>  $beforeSnapshot
     */
    public static function describeFullEdit(array $beforeSnapshot, Device $after): string
    {
        $dn = $after->name;
        $changed = self::changedAttributeKeys($beforeSnapshot, $after);

        if ($changed === []) {
            return 'Saved device for '.$dn;
        }

        if ($changed === ['remaining_time_minutes', 'total_time_allocated']) {
            return 'Set time left to '.$after->remaining_time_minutes.' min, allowance to '.$after->total_time_allocated.' min for '.$dn;
        }

        if (count($changed) === 1) {
            return match ($changed[0]) {
                'name' => 'Updated display name for '.$dn,
                'mac_address' => 'Updated Wi-Fi address for '.$dn,
                'role' => 'Set role to '.$after->role.' for '.$dn,
                'status' => 'Set internet to '.self::statusWord((string) $after->status).' for '.$dn,
                'remaining_time_minutes' => 'Set time left to '.$after->remaining_time_minutes.' min for '.$dn,
                'total_time_allocated' => 'Set allowance to '.$after->total_time_allocated.' min for '.$dn,
                'preferred_quiz_id' => 'Updated preferred quiz for '.$dn,
                'preferred_video_id' => 'Updated preferred video for '.$dn,
                default => 'Updated settings for '.$dn,
            };
        }

        $labels = [];
        foreach ($changed as $key) {
            $labels[] = match ($key) {
                'name' => 'display name',
                'mac_address' => 'Wi-Fi address',
                'role' => 'role',
                'status' => 'internet',
                'remaining_time_minutes' => 'time left',
                'total_time_allocated' => 'allowance',
                'preferred_quiz_id' => 'preferred quiz',
                'preferred_video_id' => 'preferred video',
                default => $key,
            };
        }

        if (count($labels) <= 3) {
            return 'Updated '.implode(', ', $labels).' for '.$dn;
        }

        return 'Updated several settings for '.$dn;
    }

    /**
     * @param  array<string, mixed>  $before
     * @return list<string>
     */
    private static function changedAttributeKeys(array $before, Device $after): array
    {
        $keys = ['name', 'mac_address', 'role', 'status', 'remaining_time_minutes', 'total_time_allocated', 'preferred_quiz_id', 'preferred_video_id'];
        $changed = [];
        foreach ($keys as $key) {
            $b = $before[$key] ?? null;
            $a = $after->{$key};
            if (in_array($key, ['remaining_time_minutes', 'total_time_allocated'], true)) {
                if ((int) $b !== (int) $a) {
                    $changed[] = $key;
                }

                continue;
            }
            if ($key === 'preferred_quiz_id' || $key === 'preferred_video_id') {
                $bInt = $b === null || $b === '' ? null : (int) $b;
                $aInt = $a === null ? null : (int) $a;
                if ($bInt !== $aInt) {
                    $changed[] = $key;
                }

                continue;
            }
            if ((string) $b !== (string) $a) {
                $changed[] = $key;
            }
        }

        return $changed;
    }

    private static function statusWord(string $status): string
    {
        return match ($status) {
            'blocked' => 'blocked',
            'active' => 'normal (timed)',
            'whitelisted' => 'unlimited',
            default => $status,
        };
    }
}
