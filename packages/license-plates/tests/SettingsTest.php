<?php

declare(strict_types=1);

use LicensePlates\Core\Plate;
use LicensePlates\Core\RejectReason;
use LicensePlates\Core\Settings;
use LicensePlates\Core\StateFormat;

function lpFormats(): array
{
    return [
        new StateFormat('PA', 'Pennsylvania', 'LLLNNNN'), // 3 letters
        new StateFormat('AK', 'Alaska', 'LLLNNN'),        // 3 letters
        new StateFormat('RI', 'Rhode Island', 'LLNNNN'),  // 2 letters
    ];
}

// R6/C1: picking a state clears the letter-count preference.
test('R6 selecting a state clears the letter preference', function () {
    $settings = Settings::defaults()->withLetterPreference(true, false)->settings;
    $result = $settings->selectingState('PA', ...lpFormats());

    expect($result->accepted())->toBeTrue()
        ->and($result->settings->selectedStateCode)->toBe('PA')
        ->and($result->settings->excludeTwoLetter)->toBeFalse();
});

// R6/C1: setting a preference clears the specific-state pick.
test('R6 setting a letter preference clears the state pick', function () {
    $settings = Settings::defaults()->selectingState('PA', ...lpFormats())->settings;
    $result = $settings->withLetterPreference(false, true);

    expect($result->settings->selectedStateCode)->toBeNull()
        ->and($result->settings->excludeMoreThanTwoLetter)->toBeTrue();
});

// C5: an unknown state code is refused, settings unchanged.
test('C5 selecting an unknown state is rejected', function () {
    $result = Settings::defaults()->selectingState('XX', ...lpFormats());

    expect($result->accepted())->toBeFalse()
        ->and($result->reason)->toBe(RejectReason::UnknownState);
});

// R7: excluding two-letter plates removes RI from the eligible set.
test('R7 exclude-two removes two-letter states from eligibility', function () {
    $settings = Settings::defaults()->withLetterPreference(true, false)->settings;
    $codes = array_map(fn ($f) => $f->code, $settings->eligibleAmong(...lpFormats()));

    expect($codes)->toBe(['PA', 'AK']);
});

// R8: excluding 3+-letter plates leaves only RI.
test('R8 exclude-more-than-two leaves only two-letter states', function () {
    $settings = Settings::defaults()->withLetterPreference(false, true)->settings;
    $codes = array_map(fn ($f) => $f->code, $settings->eligibleAmong(...lpFormats()));

    expect($codes)->toBe(['RI']);
});

// R12/C3: Rob's Rule cannot be enabled while a two-letter plate is up.
test("R12 Rob's Rule is refused on a two-letter plate", function () {
    $result = Settings::defaults()->withRobsRule(true, new Plate('RI', 'MZ', '0000'));

    expect($result->accepted())->toBeFalse()
        ->and($result->reason)->toBe(RejectReason::RobsRuleNeedsThreeLetters);
});

// C3: with a 3-letter plate (or no plate yet) enabling is fine.
test("C3 Rob's Rule enables on a three-letter plate and with no plate", function () {
    expect(Settings::defaults()->withRobsRule(true, new Plate('PA', 'MZE', '0000'))->accepted())->toBeTrue()
        ->and(Settings::defaults()->withRobsRule(true, null)->accepted())->toBeTrue();
});

// R18/C2: an enabled timer needs a positive duration; disabling clears it.
test('C2 timer seconds are held exactly when the timer is enabled', function () {
    $rejected = Settings::defaults()->withTimer(true, 0);
    $enabled = Settings::defaults()->withTimer(true, 30)->settings;
    $disabled = $enabled->withTimer(false, null)->settings;

    expect($rejected->reason)->toBe(RejectReason::TimerNeedsPositiveSeconds)
        ->and($enabled->timerSeconds)->toBe(30)
        ->and($disabled->timerEnabled)->toBeFalse()
        ->and($disabled->timerSeconds)->toBeNull();
});
