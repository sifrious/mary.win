<?php

declare(strict_types=1);

namespace LicensePlates\Core;

/**
 * The outcome of starting a round: a new Round, or a RejectReason (no eligible states).
 * A value, not an effect — the shell turns it into a screen.
 */
final class StartResult
{
    private function __construct(
        public readonly ?Round $round,
        public readonly ?RejectReason $reason,
    ) {
    }

    public static function ok(Round $round): self
    {
        return new self($round, null);
    }

    public static function rejected(RejectReason $reason): self
    {
        return new self(null, $reason);
    }

    public function accepted(): bool
    {
        return $this->round !== null;
    }
}
