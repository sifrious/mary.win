<?php

declare(strict_types=1);

namespace LicensePlates\Core;

/**
 * The game's decisions: start a round (pick a state, fill its pattern from the seed) and
 * judge a submitted word. Pure — same inputs, same result; no time, no randomness, no I/O.
 * Time and the seed are parameters the shell passes in.
 *
 * Reject-reason precedence when more than one applies:
 * RoundOver → EmptyWord → AlreadyPlayed → LettersOutOfOrder → NotAWord → RobsRule.
 */
final class Rules
{
    private const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const DIGITS = '0123456789';

    /**
     * R1–R5: begin a round — pick a state from the eligible set and generate a plate
     * matching its pattern (C4), all deterministically from the seed.
     */
    public function startRound(Settings $settings, int $seed, int $nowMs, StateFormat ...$formats): StartResult
    {
        $eligible = $settings->eligibleAmong(...$formats);

        if ($eligible === []) {
            return StartResult::rejected(RejectReason::NoEligibleStates);
        }

        $rng = new Rng($seed);
        $format = $eligible[$rng->pickIndex(count($eligible))];

        $letters = '';
        $numbers = '';

        foreach (str_split($format->pattern) as $slot) {
            if ($slot === 'L') {
                $letters .= self::LETTERS[$rng->pickIndex(26)];
            } else {
                $numbers .= self::DIGITS[$rng->pickIndex(10)];
            }
        }

        return StartResult::ok(Round::begin(new Plate($format->code, $letters, $numbers), $nowMs));
    }

    /** R9–R15, R18: judge one submitted word against the round. */
    public function submit(Round $round, string $raw, Settings $settings, Dictionary $dictionary, int $nowMs): SubmitResult
    {
        if ($round->isOver($settings, $nowMs)) {
            return SubmitResult::rejected(RejectReason::RoundOver, $round);
        }

        $word = strtolower(trim($raw));

        if ($word === '') {
            return SubmitResult::rejected(RejectReason::EmptyWord, $round);
        }

        if ($round->contains($word)) {
            return SubmitResult::rejected(RejectReason::AlreadyPlayed, $round);
        }

        if (! $round->plate->lettersAppearInOrderIn($word)) {
            return SubmitResult::rejected(RejectReason::LettersOutOfOrder, $round);
        }

        if (! $dictionary->contains($word)) {
            return SubmitResult::rejected(RejectReason::NotAWord, $round);
        }

        $robsRuleActive = $settings->robsRuleEnabled && $round->plate->robsRuleAvailable();

        if ($robsRuleActive && ! $round->plate->anchorsSatisfiedBy($word)) {
            return SubmitResult::rejected(RejectReason::RobsRule, $round);
        }

        return SubmitResult::ok($round->play($word), Score::forWord($word));
    }
}
