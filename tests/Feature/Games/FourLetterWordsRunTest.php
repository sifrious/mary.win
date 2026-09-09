<?php

use App\Games\FourLetterWords\Release;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    Http::fake(['https://mary.is/api/v1/me' => fn (Request $request) => Http::response([
        'account' => ['id' => $request->hasHeader('Authorization', 'Bearer other-token') ? 'acc_other' : 'acc_fixture', 'status' => 'active'],
        'product' => 'four-letter-words',
    ])]);
});

function savedRunPayload(array $words): array
{
    return [...Release::metadata(), 'submissions' => $words];
}

function savedRunUrl(): string
{
    return '/api/v1/four-letter-words/runs/00000000-0000-4000-8000-000000000001';
}

it('saves under the verified account and derives the score', function () {
    $this->withToken('fixture-token')->putJson(savedRunUrl(), [...savedRunPayload(['care', 'card']), 'account_id' => 'acc_attacker', 'streak' => 999])
        ->assertOk()->assertJsonPath('streak', 2)->assertJsonPath('submissions', ['CARE', 'CARD']);
    $this->assertDatabaseHas('four_letter_words_runs', ['account_id' => 'acc_fixture']);
    $this->assertDatabaseMissing('four_letter_words_runs', ['account_id' => 'acc_attacker']);
});

it('retries idempotently and appends progress without rewriting history', function () {
    $this->withToken('fixture-token')->putJson(savedRunUrl(), savedRunPayload(['CARE']))->assertOk();
    $this->putJson(savedRunUrl(), savedRunPayload(['care']))->assertOk();
    $this->assertDatabaseCount('four_letter_words_runs', 1);
    $this->putJson(savedRunUrl(), savedRunPayload(['CARE', 'CARD']))->assertOk()->assertJsonPath('streak', 2);
    $this->putJson(savedRunUrl(), savedRunPayload(['CARE']))->assertConflict();
    $this->putJson(savedRunUrl(), savedRunPayload(['CARE', 'CORE']))->assertConflict();
    $this->getJson(savedRunUrl())->assertOk()->assertJsonPath('submissions', ['CARE', 'CARD']);
});

it('does not expose another accounts run or overwrite it', function () {
    $this->withToken('fixture-token')->putJson(savedRunUrl(), savedRunPayload(['CARE']))->assertOk();
    $this->withToken('other-token')->getJson(savedRunUrl())->assertNotFound();
    $this->putJson(savedRunUrl(), savedRunPayload(['WORD']))->assertOk();
    $this->assertDatabaseCount('four_letter_words_runs', 2);
    $this->withToken('fixture-token')->getJson(savedRunUrl())->assertOk()->assertJsonPath('submissions', ['CARE']);
});

it('rejects invalid history and submissions after a finished run', function () {
    $this->withToken('fixture-token')->putJson(savedRunUrl(), savedRunPayload(['CARE', 'ZZZZ']))->assertOk()->assertJsonPath('status', 'lost');
    $this->putJson(savedRunUrl(), savedRunPayload(['CARE', 'ZZZZ', 'CARD']))->assertUnprocessable();
    $this->putJson(savedRunUrl(), savedRunPayload(['CARE', 'CARD']))->assertConflict();
    $this->assertSame(['CARE', 'ZZZZ'], json_decode(DB::table('four_letter_words_runs')->value('submissions'), true));
});

it('does not save for missing invalid revoked or other product credentials', function (int $status, array $body, int $expected) {
    Http::swap(new Factory);
    Http::preventStrayRequests();
    Http::fake(['https://mary.is/api/v1/me' => Http::response($body, $status)]);
    $this->withToken('fixture-token')->putJson(savedRunUrl(), savedRunPayload(['CARE']))->assertStatus($expected);
    $this->assertDatabaseCount('four_letter_words_runs', 0);
})->with([
    [401, [], 401], [403, [], 403], [500, [], 503],
    [200, ['account' => ['id' => 'acc_fixture', 'status' => 'active'], 'product' => 'license-plate-game'], 403],
    [200, ['account' => ['id' => 'acc_fixture', 'status' => 'suspended'], 'product' => 'four-letter-words'], 403],
]);

it('requires a bearer token and checks revocation on every read', function () {
    $this->putJson(savedRunUrl(), savedRunPayload(['CARE']))->assertUnauthorized();
    Http::assertNothingSent();
    $this->withToken('fixture-token')->putJson(savedRunUrl(), savedRunPayload(['CARE']))->assertOk();
    Http::swap(new Factory);
    Http::preventStrayRequests();
    Http::fake(['https://mary.is/api/v1/me' => Http::response([], 401)]);
    $this->getJson(savedRunUrl())->assertUnauthorized();
});

it('retains the save on an account host outage', function () {
    $this->withToken('fixture-token')->putJson(savedRunUrl(), savedRunPayload(['CARE']))->assertOk();
    Http::swap(new Factory);
    Http::preventStrayRequests();
    Http::fake(['*' => Http::failedConnection()]);
    $this->putJson(savedRunUrl(), savedRunPayload(['CARE', 'CARD']))->assertStatus(503);
    $this->assertSame(['CARE'], json_decode(DB::table('four_letter_words_runs')->value('submissions'), true));
});
