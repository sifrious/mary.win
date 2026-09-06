<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

function fakeGithubUser(array $overrides = []): SocialiteUser
{
    $user = new SocialiteUser;
    $user->id = $overrides['id'] ?? '12345';
    $user->nickname = $overrides['nickname'] ?? 'octocat';
    $user->name = $overrides['name'] ?? 'Octo Cat';
    $user->email = $overrides['email'] ?? 'octo@example.com';
    $user->token = 'gho_access_token';
    $user->refreshToken = 'ghr_refresh_token';

    return $user;
}

function mockGithubDriver(SocialiteUser $user): void
{
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andReturn($user);
    Socialite::shouldReceive('driver')->with('github')->andReturn($provider);
}

it('creates a new user on first GitHub login and sends them to onboarding', function () {
    mockGithubDriver(fakeGithubUser());

    $response = $this->get('/auth/github/callback');

    $response->assertRedirect(route('kite.onboarding'));
    $this->assertAuthenticated();

    $user = User::where('github_id', '12345')->first();
    expect($user)->not->toBeNull();
    expect($user->email)->toBe('octo@example.com');
    expect($user->github_token)->toBe('gho_access_token');
});

it('stores OAuth tokens encrypted at rest', function () {
    mockGithubDriver(fakeGithubUser());

    $this->get('/auth/github/callback');

    // The decrypted accessor returns the token...
    $user = User::where('github_id', '12345')->first();
    expect($user->github_token)->toBe('gho_access_token');

    // ...but the raw column is ciphertext, not the plaintext token.
    $raw = DB::table('users')->where('id', $user->id)->value('github_token');
    expect($raw)->not->toBe('gho_access_token');
});

it('logs in an existing user matched by github id and refreshes the token', function () {
    $existing = User::factory()->withGithub()->create([
        'github_id' => '12345',
        'github_token' => 'old_token',
    ]);

    mockGithubDriver(fakeGithubUser());

    $response = $this->get('/auth/github/callback');

    $response->assertRedirect(route('kite.dashboard'));
    $this->assertAuthenticatedAs($existing);
    expect($existing->fresh()->github_token)->toBe('gho_access_token');
    expect(User::count())->toBe(1);
});

it('links GitHub to an existing account matched by email', function () {
    $existing = User::factory()->create([
        'email' => 'octo@example.com',
        'github_id' => null,
    ]);

    mockGithubDriver(fakeGithubUser());

    $this->get('/auth/github/callback');

    $this->assertAuthenticatedAs($existing);
    expect($existing->fresh()->github_id)->toBe('12345');
    expect(User::count())->toBe(1);
});

it('redirects to login with an error when GitHub auth fails', function () {
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andThrow(new RuntimeException('bad state'));
    Socialite::shouldReceive('driver')->with('github')->andReturn($provider);

    $response = $this->get('/auth/github/callback');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    $this->assertGuest();
});

it('links GitHub to the already-authenticated account instead of matching by email', function () {
    // Account email deliberately differs from the GitHub email (octo@example.com),
    // so email-matching would have created/attached the wrong account.
    $user = User::factory()->create([
        'email' => 'someone-else@example.com',
        'github_id' => null,
        'github_token' => null,
    ]);

    mockGithubDriver(fakeGithubUser());

    $this->actingAs($user)->get('/auth/github/callback');

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->github_id)->toBe('12345');
    expect($user->fresh()->github_token)->toBe('gho_access_token');
    // No second account was created despite the email mismatch.
    expect(User::count())->toBe(1);
});
