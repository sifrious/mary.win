<?php

use App\Games\FourLetterWords\RunStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Sifrious\AccountsClient\Data\AppCredentials;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['games.client_id' => 'fixture-client', 'games.callback_uri' => 'https://mary.win/auth/mary/callback']);
    Http::preventStrayRequests();
});

it('plays a complete game through HTML forms without JavaScript', function () {
    $this->get('/games/four-letter-words/play')->assertOk()->assertSee('<label for="word">', false)->assertSee('Enter a word with four letters');
    $this->post('/games/four-letter-words/play', ['word' => 'CARE'])->assertRedirect();
    $this->post('/games/four-letter-words/play', ['word' => 'CARD'])->assertRedirect();
    $this->post('/games/four-letter-words/play', ['word' => 'ZZZZ'])->assertRedirect();
    $this->get('/games/four-letter-words/play')->assertOk()->assertSee('Game over')->assertSee('Streak: 2');
    $this->post('/games/four-letter-words/play', ['word' => 'CART'])->assertSessionHasErrors('word');
    $this->post('/games/four-letter-words/restart')->assertRedirect();
    $this->get('/games/four-letter-words/play')->assertSee('Streak: 0');
    $this->assertDatabaseCount('four_letter_words_runs', 0);
});

it('uses encrypted server session credentials and consumes callbacks once', function () {
    Http::fake([
        'https://mary.is/oauth/token' => Http::response(['token_type' => 'Bearer', 'access_token' => 'secret-access', 'refresh_token' => 'secret-refresh', 'expires_in' => 900]),
        'https://mary.is/api/v1/me' => Http::response(['account' => ['id' => 'acc_fixture', 'status' => 'active'], 'product' => 'four-letter-words']),
    ]);
    $response = $this->get('/auth/mary')->assertRedirect();
    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
    expect($query['code_challenge_method'])->toBe('S256');
    expect(session('game_login_attempt'))->toBeString()->not->toContain($query['state']);
    $callback = 'https://mary.win/auth/mary/callback?code=fixture&state='.$query['state'];
    $this->get($callback)->assertRedirect('/games/four-letter-words/play');
    expect(session('game_login_attempt'))->toBeNull();
    expect(session('game_credentials'))->toBeString()->not->toContain('secret-access')->not->toContain('secret-refresh');
    $this->get($callback)->assertSessionHasErrors('account');
    Http::assertSentCount(2);
});

it('refuses sign-in when credentials would enter a cookie session', function () {
    config(['session.driver' => 'cookie']);
    $this->get('/auth/mary')->assertStatus(503);
    Http::assertNothingSent();
});

it('requires explicit saving and keeps a saved run bound across account changes', function () {
    Http::fake(['https://mary.is/api/v1/me' => Http::sequence()
        ->push(['account' => ['id' => 'acc_first', 'status' => 'active'], 'product' => 'four-letter-words'])
        ->push(['account' => ['id' => 'acc_other', 'status' => 'active'], 'product' => 'four-letter-words'])]);
    $this->withSession(['game_credentials' => Crypt::encrypt(new AppCredentials('access', 'refresh', time() + 900))]);
    $this->get('/games/four-letter-words/play')->assertOk();
    $this->post('/games/four-letter-words/play', ['word' => 'CARE'])->assertRedirect();
    $this->assertDatabaseCount('four_letter_words_runs', 0);
    $this->post('/games/four-letter-words/save')->assertSessionHas('game_notice', 'Run saved to your account.');
    $this->assertDatabaseHas('four_letter_words_runs', ['account_id' => 'acc_first']);
    $this->post('/games/four-letter-words/save')->assertSessionHasErrors('account');
    $this->assertDatabaseMissing('four_letter_words_runs', ['account_id' => 'acc_other']);
});

it('clears a consumed refresh token after a lost refresh response', function () {
    Http::fake(['*' => Http::failedConnection()]);
    $this->get('/games/four-letter-words/play')->assertOk();
    $this->withSession(['game_credentials' => Crypt::encrypt(new AppCredentials('access', 'refresh', time() - 1))])
        ->post('/games/four-letter-words/save')->assertSessionHasErrors('account');
    expect(session('game_credentials'))->toBeNull();
    expect(session('flw_browser_run'))->toBeArray();
});

it('lists and resumes only the verified accounts saved runs', function () {
    $store = app(RunStore::class);
    $own = '00000000-0000-4000-8000-000000000011';
    $other = '00000000-0000-4000-8000-000000000012';
    $store->save('acc_fixture', $own, ['CARE', 'CARD']);
    $store->save('acc_other', $other, ['WORD']);
    Http::fake(['https://mary.is/api/v1/me' => Http::response(['account' => ['id' => 'acc_fixture', 'status' => 'active'], 'product' => 'four-letter-words'])]);
    $this->withSession(['game_credentials' => Crypt::encrypt(new AppCredentials('access', 'refresh', time() + 900))]);
    $this->get('/games/four-letter-words/saved')->assertOk()->assertSee($own)->assertDontSee($other);
    $this->post('/games/four-letter-words/saved/'.$other)->assertNotFound();
    $this->post('/games/four-letter-words/saved/'.$own)->assertRedirect('/games/four-letter-words/play');
    $this->get('/games/four-letter-words/play')->assertOk()->assertSee('Streak: 2')->assertSee('CARD');
    expect(session('flw_browser_run.owner'))->toBe('acc_fixture');
});
