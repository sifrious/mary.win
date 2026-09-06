<?php

declare(strict_types=1);

namespace LicensePlates\Core;

/**
 * One round in play (STATE.md: Round): the plate, when it started, whether the player has
 * finished it, and the words accepted so far. Immutable — {@see play()} and {@see finish()}
 * return new Rounds. Expiry is derived from the clock the shell passes in; the core never
 * reads time itself.
 */
final class Round
{
    /** @param list<string> $played */
    private function __construct(
        public readonly Plate $plate,
        public readonly int $startedAtMs,
        public readonly bool $ended,
        public readonly array $played,
    ) {
    }

    public static function begin(Plate $plate, int $nowMs): self
    {
        return new self($plate, $nowMs, false, []);
    }

    /** Rebuild from shell-held primitives (Livewire hydration). @param list<string> $played */
    public static function restore(Plate $plate, int $startedAtMs, bool $ended, array $played): self
    {
        return new self($plate, $startedAtMs, $ended, array_values($played));
    }

    /** Return a new round with $word recorded; the original is untouched. */
    public function play(string $word): self
    {
        return new self($this->plate, $this->startedAtMs, $this->ended, [...$this->played, $word]);
    }

    /** R16: the player ends the round by choice. */
    public function finish(): self
    {
        return new self($this->plate, $this->startedAtMs, true, $this->played);
    }

    /** C7: has this exact word already been accepted this round? */
    public function contains(string $word): bool
    {
        return in_array(strtolower($word), $this->played, true);
    }

    /** STATE.md: is_expired — R18, with the clock as an input. */
    public function isExpired(Settings $settings, int $nowMs): bool
    {
        return $settings->timerEnabled
            && $settings->timerSeconds !== null
            && ($nowMs - $this->startedAtMs) >= $settings->timerSeconds * 1000;
    }

    /** STATE.md: round_over — finished by choice or by the timer. */
    public function isOver(Settings $settings, int $nowMs): bool
    {
        return $this->ended || $this->isExpired($settings, $nowMs);
    }

    /** STATE.md: seconds_remaining — countdown display only; null when no timer. */
    public function secondsRemaining(Settings $settings, int $nowMs): ?int
    {
        if (! $settings->timerEnabled || $settings->timerSeconds === null) {
            return null;
        }

        return max(0, $settings->timerSeconds - intdiv($nowMs - $this->startedAtMs, 1000));
    }

    /** Derived: the round's score (STATE.md: round_score). */
    public function score(): int
    {
        return Score::forRound($this->played);
    }
}
