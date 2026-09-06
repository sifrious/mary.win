<?php

declare(strict_types=1);

namespace LicensePlates\Core;

/**
 * The outcome of submitting a word: either the round advances (a new Round with the word
 * recorded, and the points it earned) or the word is refused (a RejectReason and the Round
 * unchanged). A value, not an effect.
 */
final class SubmitResult
{
    private function __construct(
        public readonly bool $accepted,
        public readonly Round $round,
        public readonly ?int $points,
        public readonly ?RejectReason $reason,
    ) {
    }

    public static function ok(Round $round, int $points): self
    {
        return new self(true, $round, $points, null);
    }

    public static function rejected(RejectReason $reason, Round $round): self
    {
        return new self(false, $round, null, $reason);
    }

    /** The score to show either way: advanced on accept, unchanged on reject. */
    public function score(): int
    {
        return $this->round->score();
    }
}
