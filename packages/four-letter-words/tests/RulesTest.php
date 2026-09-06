<?php

declare(strict_types=1);

use FourLetterWords\Core\Chain;
use FourLetterWords\Core\Dictionary;
use FourLetterWords\Core\LossReason;
use FourLetterWords\Core\Rules;
use FourLetterWords\Core\Word;

/** A small fixture dictionary with real one-letter paths between its words. */
function flwDictionary(): Dictionary
{
    return new Dictionary([
        'CARE', 'CARD', 'CORD', 'WORD', 'WARD', 'WART',
        'CORE', 'BARE', 'BORE', 'CAKE', 'LAKE', 'BARD',
    ]);
}

function flwSubmit(Chain $chain, string $word): FourLetterWords\Core\MoveResult
{
    return (new Rules())->submit($chain, Word::of($word), flwDictionary());
}

// R2: the first word may be any real four-letter word; streak becomes one.
test('R2 the first real word begins the run at streak one', function () {
    $result = flwSubmit(Chain::empty(), 'CARE');

    expect($result->accepted)->toBeTrue()
        ->and($result->streak())->toBe(1)
        ->and($result->chain->currentWord()?->value)->toBe('CARE');
});

// R4: a first word that is not real ends the run at streak zero.
test('R4 a non-word first move ends the run immediately', function () {
    $result = flwSubmit(Chain::empty(), 'ZZZZ');

    expect($result->accepted)->toBeFalse()
        ->and($result->reason)->toBe(LossReason::NotAWord)
        ->and($result->streak())->toBe(0);
});

// R3: a real word one letter off, not yet used, advances the streak.
test('R3 a legal one-letter change advances the streak', function () {
    $chain = Chain::empty()->play(Word::of('CARE'));
    $result = flwSubmit($chain, 'CARD');

    expect($result->accepted)->toBeTrue()
        ->and($result->streak())->toBe(2)
        ->and($result->chain->currentWord()?->value)->toBe('CARD');
});

// R4: a non-word mid-run ends the run.
test('R4 a non-word mid-run ends the run', function () {
    $chain = Chain::empty()->play(Word::of('CARE'));
    $result = flwSubmit($chain, 'ZZZZ');

    expect($result->accepted)->toBeFalse()
        ->and($result->reason)->toBe(LossReason::NotAWord)
        ->and($result->streak())->toBe(1);
});

// R5: changing more than one letter ends the run.
test('R5 a multi-letter jump ends the run', function () {
    $chain = Chain::empty()->play(Word::of('CARE'));
    $result = flwSubmit($chain, 'WORD'); // three letters differ

    expect($result->accepted)->toBeFalse()
        ->and($result->reason)->toBe(LossReason::NotOneChange);
});

// R5: resubmitting the current word (zero changes) ends the run.
test('R5 zero change (the current word again) ends the run', function () {
    $chain = Chain::empty()->play(Word::of('CARE'));
    $result = flwSubmit($chain, 'CARE');

    expect($result->accepted)->toBeFalse()
        ->and($result->reason)->toBe(LossReason::NotOneChange);
});

// R6: a real, one-letter-off word that was already played ends the run.
test('R6 repeating an earlier word ends the run', function () {
    $chain = Chain::empty()->play(Word::of('CARE'))->play(Word::of('CORE'));
    $result = flwSubmit($chain, 'CARE'); // one letter off CORE, but already used

    expect($result->accepted)->toBeFalse()
        ->and($result->reason)->toBe(LossReason::Repeat);
});

// Precedence: NotOneChange is reported before Repeat when both apply.
test('loss-reason precedence prefers NotOneChange over Repeat', function () {
    $chain = Chain::empty()->play(Word::of('CARE'))->play(Word::of('CARD'))->play(Word::of('CORD'));
    $result = flwSubmit($chain, 'CARE'); // used AND two letters off CORD

    expect($result->reason)->toBe(LossReason::NotOneChange);
});

// A loss never mutates or extends the chain (C3/C4/C5 preserved: bad words never stored).
test('a loss leaves the chain unchanged', function () {
    $chain = Chain::empty()->play(Word::of('CARE'));
    $result = flwSubmit($chain, 'ZZZZ');

    expect($result->chain)->toBe($chain)
        ->and($result->chain->streak())->toBe(1)
        ->and($result->chain->contains(Word::of('ZZZZ')))->toBeFalse();
});

// C5 preserved on the happy path across a longer chain.
test('C5 a full legal chain keeps each step one letter apart', function () {
    $rules = new Rules();
    $dict = flwDictionary();
    $chain = Chain::empty();

    foreach (['CARE', 'CARD', 'CORD', 'WORD', 'WARD', 'WART'] as $word) {
        $chain = $rules->submit($chain, Word::of($word), $dict)->chain;
    }

    expect($chain->streak())->toBe(6)
        ->and($chain->currentWord()?->value)->toBe('WART');
});
