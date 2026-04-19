<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * Classifies HTTP requests for security audit labeling (e.g. LAN vs tailnet/WAN).
 */
final class RequestSource
{
    /**
     * True when the client IP is not in any configured trusted-local CIDR.
     */
    public static function isRemote(?Request $request = null): bool
    {
        $request ??= request();

        $ip = $request->ip();

        if ($ip === null || $ip === '') {
            return true;
        }

        return ! self::ipMatchesTrustedLocal($ip);
    }

    /**
     * @internal
     */
    public static function ipMatchesTrustedLocal(string $ip): bool
    {
        foreach (Config::get('remote_access.trusted_local_cidrs', []) as $cidr) {
            $cidr = trim((string) $cidr);
            if ($cidr === '') {
                continue;
            }
            if (self::ipMatchesCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private static function ipMatchesCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $mask] = explode('/', $cidr, 2);
        $mask = (int) $mask;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if ($mask < 0 || $mask > 32) {
                return false;
            }
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            if ($ipLong === false || $subnetLong === false) {
                return false;
            }
            $maskLong = $mask === 0 ? 0 : (-1 << (32 - $mask)) & 0xFFFFFFFF;

            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($mask < 0 || $mask > 128) {
                return false;
            }
            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);
            if ($ipBin === false || $subnetBin === false) {
                return false;
            }
            $fullBytes = intdiv($mask, 8);
            $remainderBits = $mask % 8;
            if (strncmp($ipBin, $subnetBin, $fullBytes) !== 0) {
                return false;
            }
            if ($remainderBits === 0) {
                return true;
            }
            $maskByteVal = (0xFF << (8 - $remainderBits)) & 0xFF;

            return (ord($ipBin[$fullBytes]) & $maskByteVal) === (ord($subnetBin[$fullBytes]) & $maskByteVal);
        }

        return false;
    }
}
