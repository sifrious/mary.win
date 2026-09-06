<?php

declare(strict_types=1);

namespace FourLetterWords\Core;

/**
 * The game's one decision: given the run so far and a submitted word, does the run advance
 * or end? Pure — same inputs, same result; no time, no randomness, no I/O. This is the
 * tar-pit "step": the SubmitWord event applied to a Chain.
 *
 * Loss-reason precedence when more than one applies: NotAWord → NotOneChange → Repeat.
 */
final class Rules
{
    public function submit(Chain $chain, Word $candidate, Dictionary $dictionary): MoveResult
    {
        // R4: a word that isn't real ends the run — first move or not.
        if (! $dictionary->contains($candidate)) {
            return MoveResult::lost(LossReason::NotAWord, $chain);
        }

        $current = $chain->currentWord();

        // R2: the first word may be any real word — there is nothing to change from yet.
        if ($current === null) {
            return MoveResult::accepted($chain->play($candidate));
        }

        // R5: after the first, it must change exactly one letter of the current word.
        if (! $candidate->differsInOnePosition($current)) {
            return MoveResult::lost(LossReason::NotOneChange, $chain);
        }

        // R6: and it must not repeat a word already played this run.
        if ($chain->contains($candidate)) {
            return MoveResult::lost(LossReason::Repeat, $chain);
        }

        // R3: a legal move — advance the streak.
        return MoveResult::accepted($chain->play($candidate));
    }
}
