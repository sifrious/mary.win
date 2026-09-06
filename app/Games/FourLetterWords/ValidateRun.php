<?php

namespace App\Games\FourLetterWords;

use FourLetterWords\Core\Chain;
use FourLetterWords\Core\Rules;
use FourLetterWords\Core\Word;
use InvalidArgumentException;

final class ValidateRun
{
    /** @param list<string> $submissions */
    public function validate(array $submissions): array
    {
        $chain = Chain::empty();
        $rules = new Rules;
        $dictionary = WordList::dictionary();
        $reason = null;

        foreach ($submissions as $submission) {
            if ($reason !== null) {
                throw new InvalidArgumentException('A run cannot contain submissions after a loss.');
            }

            $result = $rules->submit($chain, Word::of($submission), $dictionary);
            $chain = $result->chain;
            $reason = $result->reason;
        }

        return [
            'status' => $reason === null ? 'playing' : 'lost',
            'streak' => $chain->streak(),
            'accepted_words' => array_map(fn (Word $word) => $word->value, $chain->words()),
            'loss_reason' => $reason?->value,
        ];
    }
}
