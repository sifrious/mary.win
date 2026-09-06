<?php

declare(strict_types=1);

namespace LicensePlates\Core;

/**
 * A deterministic value stream expanded from one integer seed (a linear congruential
 * generator). This is NOT chance: same seed → same sequence, every time. The shell draws
 * the seed — its only source of variation — and the core stays pure and testable.
 */
final class Rng
{
    private int $state;

    public function __construct(int $seed)
    {
        $this->state = ($seed & 0x7FFFFFFF) ?: 1;
    }

    /** The next value in [0, 1). */
    public function nextUnit(): float
    {
        $this->state = (1664525 * $this->state + 1013904223) & 0xFFFFFFFF;

        return $this->state / 4294967296;
    }

    /** Pick an index in [0, $n). */
    public function pickIndex(int $n): int
    {
        return min($n - 1, (int) floor($this->nextUnit() * $n));
    }
}
