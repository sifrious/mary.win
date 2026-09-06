<?php

declare(strict_types=1);

namespace LicensePlates\Core;

/**
 * One state's standard driver-plate format (STATE.md: StateFormat). Immutable reference
 * data: a two-letter code, a display name, and a pattern over {L, N} giving the arrangement
 * of letter and number slots left to right (Pennsylvania: "LLLNNNN" = ABC·1234).
 */
final class StateFormat
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly string $pattern,
    ) {
    }

    /** Derived: how many letter slots this plate has (STATE.md: letter_count). */
    public function letterCount(): int
    {
        return substr_count($this->pattern, 'L');
    }

    /** R7/R8: does this state survive the active letter-count preference? */
    public function allowedBy(bool $excludeTwoLetter, bool $excludeMoreThanTwoLetter): bool
    {
        $count = $this->letterCount();

        if ($excludeTwoLetter && $count === 2) {
            return false;
        }

        if ($excludeMoreThanTwoLetter && $count > 2) {
            return false;
        }

        return true;
    }
}
