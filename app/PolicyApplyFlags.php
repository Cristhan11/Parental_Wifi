<?php

namespace App;

/**
 * Bitmask of dnsmasq / household policy work queued for a parent account.
 */
enum PolicyApplyFlags: int
{
    case None = 0;
    case Blocklist = 1;
    case DhcpBypass = 2;

    public static function full(): self
    {
        return self::from(self::Blocklist->value | self::DhcpBypass->value);
    }

    public function merge(self $other): self
    {
        return self::from($this->value | $other->value);
    }

    public function hasBlocklist(): bool
    {
        return ($this->value & self::Blocklist->value) !== 0;
    }

    public function hasDhcpBypass(): bool
    {
        return ($this->value & self::DhcpBypass->value) !== 0;
    }
}
