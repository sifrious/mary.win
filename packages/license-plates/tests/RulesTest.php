<?php

declare(strict_types=1);

use LicensePlates\Core\Dictionary;
use LicensePlates\Core\Plate;
use LicensePlates\Core\RejectReason;
use LicensePlates\Core\Round;
use LicensePlates\Core\Rules;
use LicensePlates\Core\Settings;
use LicensePlates\Core\StateFormat;
use LicensePlates\Core\SubmitResult;

/** For plate MZE: maze(4), amaze(5), amazed(6) play; mesmerize(9) and magazine(8) earn the bonus. */
function lpDictionary(): Dictionary
{
    return new Dictionary(['maze', 'mazes', 'amaze', 'amazed', 'mesmerize', 'magazine', 'me', 'cat']);
}

function lpRound(string $letters = 'MZE', array $played = [], bool $ended = false, int $startedAt = 0): Round
{
    return Round::restore(new Plate('PA', $letters, '0000'), $startedAt, $ended, $played);
}

function lpSubmit(Round $round, string $word, ?Settings $settings = null, int $now = 0): SubmitResult
{
    return (new Rules())->submit($round, $word, $settings ?? Settings::defaults(), lpDictionary(), $now);
}

// R9: a word carrying the plate letters in order is accepted.
test('R9 an in-order word is accepted', function () {
    $result = lpSubmit(lpRound(), 'amazed');

    expect($result->accepted)->toBeTrue()
        ->and($result->round->played)->toBe(['amazed']);
});

// R9/R13: reordering the plate letters is refused, and says why.
test('R13 an out-of-order word is refused with the rule it broke', function () {
    $result = lpSubmit(lpRound(), 'zema');

    expect($result->accepted)->toBeFalse()
        ->and($result->reason)->toBe(RejectReason::LettersOutOfOrder);
});

// R10: a non-word is refused.
test('R10 a word missing from the list is refused', function () {
    $result = lpSubmit(lpRound(), 'mze');

    expect($result->reason)->toBe(RejectReason::NotAWord);
});

// R11: Rob's Rule anchors are enforced when active.
test("R11 Rob's Rule refuses an unanchored word and accepts an anchored one", function () {
    $settings = Settings::defaults()->withRobsRule(true, null)->settings;

    expect(lpSubmit(lpRound(), 'maze', $settings)->accepted)->toBeTrue()
        ->and(lpSubmit(lpRound(), 'amazed', $settings)->reason)->toBe(RejectReason::RobsRule);
});

// R12: on a two-letter plate an enabled Rob's Rule is inert.
test("R12 Rob's Rule is inert on a plate with fewer than three letters", function () {
    $settings = Settings::defaults()->withRobsRule(true, null)->settings;

    // 'amaze' does not start with M — anchors fail — yet it is accepted, because the rule
    // is unavailable on a two-letter plate.
    expect(lpSubmit(lpRound('ME'), 'amaze', $settings)->accepted)->toBeTrue();
});

// R14: an accepted word under eight letters earns one point.
test('R14 a short word earns one point', function () {
    $result = lpSubmit(lpRound(), 'maze');

    expect($result->points)->toBe(1)->and($result->score())->toBe(1);
});

// R15: eight or more letters earns two points.
test('R15 an eight-plus-letter word earns two points', function () {
    expect(lpSubmit(lpRound(), 'magazine')->points)->toBe(2) // exactly 8
        ->and(lpSubmit(lpRound(), 'mesmerize')->points)->toBe(2); // 9
});

// C7: the same word never scores twice.
test('C7 a repeated word is refused', function () {
    $result = lpSubmit(lpRound(played: ['maze']), 'maze');

    expect($result->reason)->toBe(RejectReason::AlreadyPlayed)
        ->and($result->round->played)->toBe(['maze']);
});

// R18: an expired timer refuses further words.
test('R18 submissions after the timer expires are refused', function () {
    $settings = Settings::defaults()->withTimer(true, 10)->settings;
    $result = lpSubmit(lpRound(startedAt: 0), 'maze', $settings, now: 20_000);

    expect($result->reason)->toBe(RejectReason::RoundOver);
});

// R16: finishing is by choice; the score sums 1s and 2s.
test('R16 the round score sums word scores', function () {
    $round = lpRound(played: ['maze', 'mesmerize'])->finish();

    expect($round->ended)->toBeTrue()->and($round->score())->toBe(3);
});

// A refusal never grows the round (bad words are never stored).
test('a refusal leaves the round unchanged', function () {
    $round = lpRound(played: ['maze']);
    $result = lpSubmit($round, 'zzz');

    expect($result->round)->toBe($round)->and($result->round->played)->toBe(['maze']);
});

// R19: the plate numbers play no part in judging or scoring.
test('R19 plate numbers never affect validity or score', function () {
    $a = lpSubmit(Round::restore(new Plate('PA', 'MZE', '1111'), 0, false, []), 'amazed');
    $b = lpSubmit(Round::restore(new Plate('PA', 'MZE', '9999'), 0, false, []), 'amazed');

    expect($a->accepted)->toBe($b->accepted)->and($a->points)->toBe($b->points);
});

// --- startRound ---------------------------------------------------------------

function lpAllFormats(): array
{
    return [
        new StateFormat('PA', 'Pennsylvania', 'LLLNNNN'),
        new StateFormat('AK', 'Alaska', 'LLLNNN'),
        new StateFormat('RI', 'Rhode Island', 'LLNNNN'),
    ];
}

// R2/C4: the generated plate matches the chosen state's pattern.
test('R2 the generated plate matches the state pattern', function () {
    $settings = Settings::defaults()->selectingState('PA', ...lpAllFormats())->settings;
    $result = (new Rules())->startRound($settings, 123, 1_000, ...lpAllFormats());

    expect($result->accepted())->toBeTrue()
        ->and($result->round->plate->stateCode)->toBe('PA')
        ->and(strlen($result->round->plate->letters))->toBe(3)
        ->and(strlen($result->round->plate->numbers))->toBe(4);
});

// R3: the seed is the only source of variation — same seed, same plate; seeds vary.
test('R3 plates are deterministic per seed and vary across seeds', function () {
    $rules = new Rules();
    $settings = Settings::defaults()->selectingState('PA', ...lpAllFormats())->settings;

    $a = $rules->startRound($settings, 7, 0, ...lpAllFormats())->round->plate;
    $b = $rules->startRound($settings, 7, 0, ...lpAllFormats())->round->plate;

    expect($a->letters)->toBe($b->letters)->and($a->numbers)->toBe($b->numbers);

    $seen = [];
    foreach (range(1, 20) as $seed) {
        $seen[$rules->startRound($settings, $seed, 0, ...lpAllFormats())->round->plate->letters] = true;
    }

    expect(count($seen))->toBeGreaterThan(1);
});

// R5/R7: with exclude-two active, a random draw never lands on a two-letter state.
test('R5 a random draw honors the letter preference', function () {
    $rules = new Rules();
    $settings = Settings::defaults()->withLetterPreference(true, false)->settings;

    foreach (range(1, 30) as $seed) {
        $round = $rules->startRound($settings, $seed, 0, ...lpAllFormats())->round;
        expect($round->plate->stateCode)->not->toBe('RI');
    }
});

// A preference that empties the eligible set refuses to start.
test('an impossible preference refuses to start a round', function () {
    $settings = Settings::defaults()->withLetterPreference(true, true)->settings;
    $result = (new Rules())->startRound($settings, 1, 0, new StateFormat('RI', 'Rhode Island', 'LLNNNN'), new StateFormat('PA', 'Pennsylvania', 'LLLNNNN'));

    expect($result->accepted())->toBeFalse()
        ->and($result->reason)->toBe(RejectReason::NoEligibleStates);
});
