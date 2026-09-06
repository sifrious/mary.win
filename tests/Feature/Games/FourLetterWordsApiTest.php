<?php

use App\Games\FourLetterWords\Release;

function gamePayload(array $submissions): array
{
    return array_merge(Release::metadata(), ['submissions' => $submissions]);
}

it('publishes the canonical dictionary identity without claiming a license', function () {
    $this->getJson('/api/v1/four-letter-words/metadata')->assertOk()
        ->assertJsonPath('dictionary_version', hash_file('sha256', base_path('packages/four-letter-words/resources/words.txt')))
        ->assertJsonPath('offline_distribution_ready', false);
});

it('replays normalized submissions and derives the score', function () {
    $this->postJson('/api/v1/four-letter-words/validate-run', gamePayload([' care ', 'card']))
        ->assertOk()->assertExactJson(['status' => 'playing', 'streak' => 2, 'accepted_words' => ['CARE', 'CARD'], 'loss_reason' => null]);
});

it('uses the existing loss rules and preserves accepted progress', function (array $words, string $reason, int $streak) {
    $this->postJson('/api/v1/four-letter-words/validate-run', gamePayload($words))
        ->assertOk()->assertJsonPath('status', 'lost')->assertJsonPath('loss_reason', $reason)->assertJsonPath('streak', $streak);
})->with([
    [['CARE', 'ZZZZ'], 'not_a_word', 1],
    [['CARE', 'WORD'], 'not_one_change', 1],
    [['CARE', 'CORE', 'CARE'], 'repeat', 2],
]);

it('rejects malformed submissions and play after loss', function (array $words) {
    $this->postJson('/api/v1/four-letter-words/validate-run', gamePayload($words))
        ->assertUnprocessable()->assertJsonValidationErrors('submissions');
})->with([[['1234']], [['CARE', 'ZZZZ', 'CARD']]]);

it('refuses stale rules and dictionary versions', function (string $field, string $value) {
    $payload = gamePayload(['CARE']);
    $payload[$field] = $value;
    $this->postJson('/api/v1/four-letter-words/validate-run', $payload)->assertConflict();
})->with([['rules_version', '0'], ['dictionary_version', str_repeat('0', 64)]]);

it('validates an empty run without trusting caller score or account claims', function () {
    $this->postJson('/api/v1/four-letter-words/validate-run', array_merge(gamePayload([]), ['streak' => 999, 'account_id' => 'someone-else']))
        ->assertOk()->assertJsonPath('streak', 0)->assertJsonMissingPath('account_id');
});
