<?php

declare(strict_types=1);

use App\Livewire\Games\LicensePlates;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/** Put a known plate up so word rules are deterministic against the real ENABLE list. */
function lpWithPlate(string $letters = 'MZE', string $numbers = '0000'): Testable
{
    return Livewire::test(LicensePlates::class)
        ->set('plate', ['state_code' => 'PA', 'letters' => $letters, 'numbers' => $numbers])
        ->set('startedAtMs', 0)
        ->set('ended', false)
        ->set('played', []);
}

/** Capture the last method return value from a Livewire test component. */
function lpReturn(Testable $component): array
{
    $captured = null;
    $component->assertReturned(function ($r) use (&$captured) {
        $captured = $r;

        return true;
    });

    return $captured;
}

// R1 — the game page loads with the component mounted.
it('R1 renders the game page', function () {
    $this->withoutVite();

    $this->get('/games/license-plates')
        ->assertOk()
        ->assertSeeLivewire(LicensePlates::class);
});

// R2/R4 — a selected state's plate matches its pattern (PA: 3 letters, 4 numbers).
it('R2 generates a plate matching the selected state pattern', function () {
    Livewire::test(LicensePlates::class)
        ->call('selectState', 'PA')
        ->call('newPlate', 7)
        ->assertReturned(function (array $res) {
            return $res['ok'] === true
                && $res['plate']['code'] === 'PA'
                && strlen($res['plate']['letters']) === 3
                && count($res['plate']['cells']) === 7;
        });
});

// R3 — the same seed reproduces the same plate; different seeds vary.
it('R3 plates are deterministic per seed and vary across seeds', function () {
    $component = Livewire::test(LicensePlates::class)->call('selectState', 'PA');

    $a = lpReturn($component->call('newPlate', 7));
    $b = lpReturn($component->call('newPlate', 7));
    $c = lpReturn($component->call('newPlate', 8));

    expect($a['plate']['letters'])->toBe($b['plate']['letters'])
        ->and($a['plate']['letters'] === $c['plate']['letters'] && $a['plate']['cells'] === $c['plate']['cells'])->toBeFalse();
});

// R5/R7 — with exclude-two active, random draws avoid two-letter states.
it('R5 honors the letter preference on random draws', function () {
    $component = Livewire::test(LicensePlates::class)->call('setLetterPreference', true, false);

    foreach (range(1, 15) as $seed) {
        $res = lpReturn($component->call('newPlate', $seed));
        expect(strlen($res['plate']['letters']))->not->toBe(2);
    }
});

// R6 — picking a state clears the preference, and vice versa (C1).
it('R6 keeps state pick and letter preference mutually exclusive', function () {
    Livewire::test(LicensePlates::class)
        ->call('setLetterPreference', true, false)
        ->assertSet('excludeTwoLetter', true)
        ->call('selectState', 'PA')
        ->assertSet('selectedStateCode', 'PA')
        ->assertSet('excludeTwoLetter', false)
        ->call('setLetterPreference', false, true)
        ->assertSet('selectedStateCode', null)
        ->assertSet('excludeMoreThanTwoLetter', true);
});

// R8 — excluding 3+-letter plates leaves only short-lettered states eligible.
it('R8 only short-lettered plates remain under exclude-more-than-two', function () {
    $component = Livewire::test(LicensePlates::class)->call('setLetterPreference', false, true);

    foreach (range(1, 10) as $seed) {
        $res = lpReturn($component->call('newPlate', $seed));
        expect(strlen($res['plate']['letters']))->toBeLessThanOrEqual(2);
    }
});

// R9/R14 — an in-order word is accepted for one point.
it('R9 accepts a word carrying the plate letters in order', function () {
    lpWithPlate()
        ->call('submit', 'amazed')
        ->assertReturned(['ok' => true, 'word' => 'amazed', 'points' => 1, 'score' => 1])
        ->assertSet('played', ['amazed']);
});

// R13 — refusals name the rule that was broken.
it('R13 refuses out-of-order and unreal words with the failed rule', function () {
    lpWithPlate()
        ->call('submit', 'zema')
        ->assertReturned(fn (array $r) => $r['ok'] === false && $r['reason'] === 'letters_out_of_order');

    lpWithPlate()
        ->call('submit', 'mze')
        ->assertReturned(fn (array $r) => $r['ok'] === false && $r['reason'] === 'not_a_word');
});

// R10 — only real words are recorded.
it('R10 records nothing for an unreal word', function () {
    lpWithPlate()
        ->call('submit', 'mze')
        ->assertSet('played', []);
});

// R11 — Rob's Rule requires the anchors.
it("R11 enforces Rob's Rule anchors when enabled", function () {
    lpWithPlate()
        ->call('setRobsRule', true)
        ->call('submit', 'maze')
        ->assertReturned(fn (array $r) => $r['ok'] === true)
        ->call('submit', 'amazed')
        ->assertReturned(fn (array $r) => $r['ok'] === false && $r['reason'] === 'robs_rule');
});

// R12 — Rob's Rule cannot be enabled on a two-letter plate.
it("R12 refuses Rob's Rule on a two-letter plate", function () {
    lpWithPlate('MZ')
        ->call('setRobsRule', true)
        ->assertReturned(['ok' => false, 'reason' => 'robs_rule_needs_three_letters'])
        ->assertSet('robsRuleEnabled', false);
});

// R15 — eight or more letters earns two points.
it('R15 awards two points for an eight-plus-letter word', function () {
    lpWithPlate()
        ->call('submit', 'magazine')
        ->assertReturned(['ok' => true, 'word' => 'magazine', 'points' => 2, 'score' => 2]);
});

// R16/R17 — finishing reports score and played words, and reveals nothing else.
it('R16 reports score and words on finish, revealing no unplayed word', function () {
    lpWithPlate()
        ->call('submit', 'magazine')
        ->call('submit', 'maze')
        ->call('finish')
        ->assertReturned(function (array $r) {
            return $r === ['ok' => true, 'score' => 3, 'words' => ['magazine', 'maze']];
        });
});

// R18 — after the timer expires, submissions are refused.
it('R18 refuses words after the timer expires', function () {
    lpWithPlate()
        ->call('setTimer', true, 10)
        ->set('startedAtMs', 1) // long past relative to the real clock
        ->call('submit', 'maze')
        ->assertReturned(fn (array $r) => $r['ok'] === false && $r['reason'] === 'round_over');
});

// R19 — plate numbers never affect the judgment.
it('R19 judges identically whatever the plate numbers are', function () {
    $a = lpReturn(lpWithPlate('MZE', '1111')->call('submit', 'amazed'));
    $b = lpReturn(lpWithPlate('MZE', '9999')->call('submit', 'amazed'));

    expect($a)->toBe($b);
});

// C7 — a word scores once.
it('C7 refuses a repeated word', function () {
    lpWithPlate()
        ->call('submit', 'maze')
        ->call('submit', 'maze')
        ->assertReturned(fn (array $r) => $r['ok'] === false && $r['reason'] === 'already_played')
        ->assertSet('played', ['maze']);
});

// Guard — no round yet.
it('refuses a submission before any plate exists', function () {
    Livewire::test(LicensePlates::class)
        ->call('submit', 'maze')
        ->assertReturned(['ok' => false, 'reason' => 'no_round']);
});
