<?php

declare(strict_types=1);

use FourLetterWords\Core\Chain;
use FourLetterWords\Core\Word;

test('an empty chain has no streak and no current word', function () {
    $chain = Chain::empty();

    expect($chain->isEmpty())->toBeTrue()
        ->and($chain->streak())->toBe(0)
        ->and($chain->currentWord())->toBeNull();
});

// Immutability: play() never mutates the receiver (purity of the core).
test('play returns a new chain and leaves the original untouched', function () {
    $original = Chain::empty();
    $next = $original->play(Word::of('CARE'));

    expect($original->streak())->toBe(0)
        ->and($next->streak())->toBe(1)
        ->and($next)->not->toBe($original);
});

// Derived: streak = number of plays (STATE.md).
test('streak equals the number of plays', function () {
    $chain = Chain::empty()->play(Word::of('CARE'))->play(Word::of('CARD'));

    expect($chain->streak())->toBe(2);
});

// Derived: current word = last play.
test('current word is the most recent play', function () {
    $chain = Chain::empty()->play(Word::of('CARE'))->play(Word::of('CARD'));

    expect($chain->currentWord()?->value)->toBe('CARD');
});

// Derived: used-words membership (C4 building block).
test('contains reports whether a word has been played', function () {
    $chain = Chain::empty()->play(Word::of('CARE'));

    expect($chain->contains(Word::of('care')))->toBeTrue()
        ->and($chain->contains(Word::of('CARD')))->toBeFalse();
});

// C3: order is preserved chronologically; R7 shows it reversed.
test('words are chronological and reversed is most-recent-first', function () {
    $chain = Chain::empty()->play(Word::of('CARE'))->play(Word::of('CARD'))->play(Word::of('CORD'));

    $chronological = array_map(fn (Word $w) => $w->value, $chain->words());
    $reversed = array_map(fn (Word $w) => $w->value, $chain->reversed());

    expect($chronological)->toBe(['CARE', 'CARD', 'CORD'])
        ->and($reversed)->toBe(['CORD', 'CARD', 'CARE']);
});

test('fromWords rebuilds a chain in order', function () {
    $chain = Chain::fromWords(Word::of('CARE'), Word::of('CARD'));

    expect($chain->streak())->toBe(2)
        ->and($chain->currentWord()?->value)->toBe('CARD');
});
