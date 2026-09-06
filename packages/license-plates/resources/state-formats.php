<?php

declare(strict_types=1);

/*
 * Standard *driver* plate formats for the 50 states, as patterns over {L, N}
 * (L = a letter slot, N = a number slot), in left-to-right order.
 *
 * ACCURACY: a first-pass, "accurate-ish" set (SPEC assumption). Entries marked
 * 'approx' are county-coded, historically numeric-only, or otherwise uncertain and
 * were given a plausible letter-bearing shape so the word game is playable — every
 * plate MUST contain at least one letter (STATE.md C9). Corrections welcome; the
 * engine only cares that each pattern has an L.
 */

return [
    ['code' => 'AL', 'name' => 'Alabama', 'pattern' => 'NNLLNNN', 'approx' => true],
    ['code' => 'AK', 'name' => 'Alaska', 'pattern' => 'LLLNNN'],
    ['code' => 'AZ', 'name' => 'Arizona', 'pattern' => 'LLLNNNN'],
    ['code' => 'AR', 'name' => 'Arkansas', 'pattern' => 'NNNLLL'],
    ['code' => 'CA', 'name' => 'California', 'pattern' => 'NLLLNNN'],
    ['code' => 'CO', 'name' => 'Colorado', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'CT', 'name' => 'Connecticut', 'pattern' => 'LLNNNNN', 'approx' => true],
    ['code' => 'DE', 'name' => 'Delaware', 'pattern' => 'LNNNNN', 'approx' => true],
    ['code' => 'FL', 'name' => 'Florida', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'GA', 'name' => 'Georgia', 'pattern' => 'LLLNNNN'],
    ['code' => 'HI', 'name' => 'Hawaii', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'ID', 'name' => 'Idaho', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'IL', 'name' => 'Illinois', 'pattern' => 'LLNNNNN'],
    ['code' => 'IN', 'name' => 'Indiana', 'pattern' => 'NNNLLL', 'approx' => true],
    ['code' => 'IA', 'name' => 'Iowa', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'KS', 'name' => 'Kansas', 'pattern' => 'NNNLLL', 'approx' => true],
    ['code' => 'KY', 'name' => 'Kentucky', 'pattern' => 'NNNLLL', 'approx' => true],
    ['code' => 'LA', 'name' => 'Louisiana', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'ME', 'name' => 'Maine', 'pattern' => 'NNNNLL', 'approx' => true],
    ['code' => 'MD', 'name' => 'Maryland', 'pattern' => 'NLLNNNN', 'approx' => true],
    ['code' => 'MA', 'name' => 'Massachusetts', 'pattern' => 'NLLLNN', 'approx' => true],
    ['code' => 'MI', 'name' => 'Michigan', 'pattern' => 'LLLNNNN'],
    ['code' => 'MN', 'name' => 'Minnesota', 'pattern' => 'LLLNNN'],
    ['code' => 'MS', 'name' => 'Mississippi', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'MO', 'name' => 'Missouri', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'MT', 'name' => 'Montana', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'NE', 'name' => 'Nebraska', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'NV', 'name' => 'Nevada', 'pattern' => 'NNNLLL', 'approx' => true],
    ['code' => 'NH', 'name' => 'New Hampshire', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'NJ', 'name' => 'New Jersey', 'pattern' => 'LNNLLL', 'approx' => true],
    ['code' => 'NM', 'name' => 'New Mexico', 'pattern' => 'NNNLLL', 'approx' => true],
    ['code' => 'NY', 'name' => 'New York', 'pattern' => 'LLLNNNN'],
    ['code' => 'NC', 'name' => 'North Carolina', 'pattern' => 'LLLNNNN', 'approx' => true],
    ['code' => 'ND', 'name' => 'North Dakota', 'pattern' => 'NNNLLL', 'approx' => true],
    ['code' => 'OH', 'name' => 'Ohio', 'pattern' => 'LLLNNNN'],
    ['code' => 'OK', 'name' => 'Oklahoma', 'pattern' => 'LLLNNNN', 'approx' => true],
    ['code' => 'OR', 'name' => 'Oregon', 'pattern' => 'NNNLLL', 'approx' => true],
    ['code' => 'PA', 'name' => 'Pennsylvania', 'pattern' => 'LLLNNNN'],
    ['code' => 'RI', 'name' => 'Rhode Island', 'pattern' => 'LLNNNN', 'approx' => true],
    ['code' => 'SC', 'name' => 'South Carolina', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'SD', 'name' => 'South Dakota', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'TN', 'name' => 'Tennessee', 'pattern' => 'LLLNNNN', 'approx' => true],
    ['code' => 'TX', 'name' => 'Texas', 'pattern' => 'LLLNNNN'],
    ['code' => 'UT', 'name' => 'Utah', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'VT', 'name' => 'Vermont', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'VA', 'name' => 'Virginia', 'pattern' => 'LLLNNNN'],
    ['code' => 'WA', 'name' => 'Washington', 'pattern' => 'LLLNNNN'],
    ['code' => 'WV', 'name' => 'West Virginia', 'pattern' => 'LLLNNN', 'approx' => true],
    ['code' => 'WI', 'name' => 'Wisconsin', 'pattern' => 'LLLNNNN'],
    ['code' => 'WY', 'name' => 'Wyoming', 'pattern' => 'NLLLNN', 'approx' => true],
];
