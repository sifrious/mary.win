<?php

declare(strict_types=1);

namespace FourLetterWords\Core;

/** Why a submitted word ended the run. */
enum LossReason: string
{
    case NotAWord = 'not_a_word';         // R4: not in the dictionary
    case NotOneChange = 'not_one_change'; // R5: changed other than exactly one letter
    case Repeat = 'repeat';               // R6: already played this run
}
