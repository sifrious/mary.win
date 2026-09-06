<?php

declare(strict_types=1);

namespace FourLetterWords\Core;

/**
 * The outcome of submitting a word: either the run advances (a new Chain with the word
 * appended) or the run ends (a LossReason and the Chain unchanged). A value, not an
 * effect — the shell turns it into screen updates.
 */
final class MoveResult
{
    private function __construct(
        public readonly bool $accepted,
        public readonly Chain $chain,
        public readonly ?LossReason $reason,
    ) {
    }

    public static function accepted(Chain $chain): self
    {
        return new self(true, $chain, null);
    }

    public static function lost(LossReason $reason, Chain $chain): self
    {
        return new self(false, $chain, $reason);
    }

    /**
     * The streak to show: the chain's length either way — one longer on an accepted move
     * (R3), or the unchanged final streak on a loss (R7).
     */
    public function streak(): int
    {
        return $this->chain->streak();
    }
}
