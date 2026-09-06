<?php

use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function githubRepoPayload(int $id): array
{
    return [
        'id' => $id,
        'name' => "repo-{$id}",
        'full_name' => "octocat/repo-{$id}",
        'description' => "Repository {$id}",
        'private' => $id % 2 === 0,
        'html_url' => "https://github.com/octocat/repo-{$id}",
        'default_branch' => 'main',
    ];
}

it('syncs repositories and follows pagination past the first page', function () {
    $user = User::factory()->withGithub()->create();

    $pageOne = collect(range(1, 100))->map(fn ($i) => githubRepoPayload($i))->all();
    $pageTwo = collect(range(101, 105))->map(fn ($i) => githubRepoPayload($i))->all();

    Http::fake([
        'api.github.com/user/repos*' => Http::sequence()
            ->push($pageOne, 200)
            ->push($pageTwo, 200),
    ]);

    $this->actingAs($user)
        ->post(route('kite.repositories.sync'))
        ->assertRedirect(route('kite.repositories.select'))
        ->assertSessionHas('success');

    // 100 (full page → keep paging) + 5 (short page → stop) = 105.
    expect(Repository::where('user_id', $user->id)->count())->toBe(105);
});

it('flashes an error when GitHub is unreachable', function () {
    $user = User::factory()->withGithub()->create();

    Http::fake(['api.github.com/user/repos*' => Http::response([], 500)]);

    $this->actingAs($user)
        ->post(route('kite.repositories.sync'))
        ->assertSessionHas('error');

    expect(Repository::count())->toBe(0);
});

it('requires authentication to sync', function () {
    $this->post(route('kite.repositories.sync'))->assertRedirect(route('login'));
});
