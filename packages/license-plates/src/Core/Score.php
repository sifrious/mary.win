<?php

declare(strict_types=1);

namespace LicensePlates\Core;

/**
 * Scoring (STATE.md: word_score, round_score): 1 point per accepted word, 2 for a word of
 * eight or more letters (R14, R15). Pure derivations — nothing here is ever stored.
 */
final class Score
{
    public const BONUS_MIN_LENGTH = 8;

    public static function forWord(string $word): int
    {
        return strlen($word) >= self::BONUS_MIN_LENGTH ? 2 : 1;
    }

    /** @param list<string> $played */
    public static function forRound(array $played): int
    {
        return array_sum(array_map(self::forWord(...), $played));
    }
}
