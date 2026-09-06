<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows Continue with GitHub to a guest', function () {
    $this->get(route('kite.index'))
        ->assertOk()
        ->assertSee('Continue with GitHub');
});

it('prompts an authenticated user without a GitHub token to connect', function () {
    $user = User::factory()->create(['github_id' => null, 'github_token' => null]);

    $this->actingAs($user)->get(route('kite.index'))
        ->assertOk()
        ->assertSee('Continue with GitHub')
        ->assertDontSee('Import a Repository');
});

it('shows the import form to an authenticated user who has connected GitHub', function () {
    $user = User::factory()->withGithub()->create();

    $this->actingAs($user)->get(route('kite.index'))
        ->assertOk()
        ->assertSee('Import a Repository')
        ->assertDontSee('Continue with GitHub');
});
