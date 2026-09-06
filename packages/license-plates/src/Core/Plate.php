<?php

declare(strict_types=1);

namespace LicensePlates\Core;

/**
 * A generated plate: which state it belongs to, its letters in left-to-right order, and
 * its numbers. The letters are the game material; the numbers are decoration only (R19).
 * The two word-shape questions — subsequence order (R9) and Rob's Rule anchors (R11) —
 * live here because they are properties of the plate's letters.
 */
final class Plate
{
    public function __construct(
        public readonly string $stateCode,
        public readonly string $letters,
        public readonly string $numbers,
    ) {
    }

    /** R9: the plate letters appear in $word in their original left-to-right order. */
    public function lettersAppearInOrderIn(string $word): bool
    {
        $plate = strtoupper($this->letters);
        $candidate = strtoupper($word);
        $needle = 0;
        $length = strlen($plate);

        for ($i = 0, $n = strlen($candidate); $i < $n && $needle < $length; $i++) {
            if ($candidate[$i] === $plate[$needle]) {
                $needle++;
            }
        }

        return $needle === $length;
    }

    /** R11: $word begins with the plate's first letter and ends with its last. */
    public function anchorsSatisfiedBy(string $word): bool
    {
        if ($word === '') {
            return false;
        }

        $plate = strtoupper($this->letters);
        $candidate = strtoupper($word);

        return $candidate[0] === $plate[0]
            && $candidate[strlen($candidate) - 1] === $plate[strlen($plate) - 1];
    }

    /** R12 (STATE.md: robs_rule_available): Rob's Rule needs three or more letters. */
    public function robsRuleAvailable(): bool
    {
        return strlen($this->letters) >= 3;
    }
}
