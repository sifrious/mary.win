<?php

declare(strict_types=1);

namespace LicensePlates\Core;

/**
 * Why an input was refused (R13; also settings/round guards). String-backed so the shell
 * can hand the value straight to the browser, mirroring four-letter-words' LossReason.
 */
enum RejectReason: string
{
    case NoRound = 'no_round';
    case RoundOver = 'round_over';
    case EmptyWord = 'empty';
    case AlreadyPlayed = 'already_played';
    case LettersOutOfOrder = 'letters_out_of_order';
    case NotAWord = 'not_a_word';
    case RobsRule = 'robs_rule';
    case NoEligibleStates = 'no_eligible_states';
    case UnknownState = 'unknown_state';
    case RobsRuleNeedsThreeLetters = 'robs_rule_needs_three_letters';
    case TimerNeedsPositiveSeconds = 'timer_needs_positive_seconds';
}
