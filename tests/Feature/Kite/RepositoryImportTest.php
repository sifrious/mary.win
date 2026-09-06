<?php

use App\Models\Repository;
use App\Models\RepositoryToRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function fakeForeignRepo(): void
{
    Http::fake([
        'api.github.com/repos/owner/project' => Http::response([
            'id' => 999,
            'name' => 'project',
            'full_name' => 'owner/project',
            'description' => 'A cool project',
            'private' => false,
            'html_url' => 'https://github.com/owner/project',
            'default_branch' => 'main',
            'owner' => ['login' => 'owner'],
            'stargazers_count' => 42,
            'forks_count' => 7,
            'language' => 'PHP',
        ], 200),
        'api.github.com/user' => Http::response(['login' => 'reader'], 200),
    ]);
}

it('imports a foreign repository into the reading list', function () {
    $user = User::factory()->withGithub()->create();
    fakeForeignRepo();

    $this->actingAs($user)
        ->post(route('kite.repositories.import'), ['repository_url' => 'https://github.com/owner/project'])
        ->assertRedirect();

    $repo = RepositoryToRead::where('user_id', $user->id)->first();
    expect($repo)->not->toBeNull();
    expect($repo->full_name)->toBe('owner/project');
    expect($repo->owner)->toBe('owner');
    expect($user->fresh()->last_read_repository_to_read_id)->toBe($repo->id);
});

it('rejects importing your own repository', function () {
    $user = User::factory()->withGithub()->create();
    Http::fake([
        'api.github.com/repos/owner/project' => Http::response([
            'id' => 999, 'name' => 'project', 'full_name' => 'owner/project',
            'description' => null, 'private' => false, 'html_url' => 'https://github.com/owner/project',
            'default_branch' => 'main', 'owner' => ['login' => 'owner'],
            'stargazers_count' => 0, 'forks_count' => 0, 'language' => 'PHP',
        ], 200),
        // The authenticated GitHub user IS the repo owner.
        'api.github.com/user' => Http::response(['login' => 'owner'], 200),
    ]);

    $this->actingAs($user)
        ->post(route('kite.repositories.import'), ['repository_url' => 'https://github.com/owner/project'])
        ->assertSessionHas('error');

    expect(RepositoryToRead::count())->toBe(0);
});

it('does not duplicate a repository already in the reading list', function () {
    $user = User::factory()->withGithub()->create();
    RepositoryToRead::factory()->for($user)->create(['github_id' => '999']);
    fakeForeignRepo();

    $this->actingAs($user)
        ->post(route('kite.repositories.import'), ['repository_url' => 'https://github.com/owner/project'])
        ->assertRedirect();

    expect(RepositoryToRead::where('user_id', $user->id)->count())->toBe(1);
});

it('rejects a repository already in your own collection', function () {
    $user = User::factory()->withGithub()->create();
    Repository::factory()->for($user)->create(['github_id' => '999']);
    fakeForeignRepo();

    $this->actingAs($user)
        ->post(route('kite.repositories.import'), ['repository_url' => 'https://github.com/owner/project'])
        ->assertSessionHas('error');

    expect(RepositoryToRead::count())->toBe(0);
});

it('validates the repository URL format', function () {
    $user = User::factory()->withGithub()->create();

    $this->actingAs($user)
        ->post(route('kite.repositories.import'), ['repository_url' => 'https://example.com/not-github'])
        ->assertSessionHasErrors('repository_url');

    expect(RepositoryToRead::count())->toBe(0);
});
