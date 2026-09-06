<?php

use App\Analysis\AnalysisResult;
use App\Models\Repository;
use App\Models\RepositoryFileTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function fakeRepoTree(): void
{
    Http::fake([
        'api.github.com/repos/octocat/proj/branches/main' => Http::response([
            'commit' => ['sha' => 'abc123'],
        ], 200),
        'api.github.com/repos/octocat/proj/git/trees/*' => Http::response([
            'truncated' => false,
            'tree' => [
                ['type' => 'blob', 'path' => 'README.md', 'size' => 100, 'sha' => 'sha-readme'],
                ['type' => 'blob', 'path' => 'src/App.php', 'size' => 200, 'sha' => 'sha-app'],
                ['type' => 'tree', 'path' => 'src'],
                ['type' => 'blob', 'path' => 'vendor/ignore.php', 'size' => 10, 'sha' => 'sha-vendor'],
            ],
        ], 200),
        'api.github.com/repos/octocat/proj/contents/*' => Http::response([
            'content' => base64_encode("<?php\nroute('home');\nstrlen('abc');\n"),
            'encoding' => 'base64',
        ], 200),
    ]);
}

it('analyzes structure and stores the filtered file tree', function () {
    $user = User::factory()->withGithub()->create();
    $repo = Repository::factory()->for($user)->create([
        'full_name' => 'octocat/proj',
        'default_branch' => 'main',
    ]);
    fakeRepoTree();

    $this->actingAs($user)
        ->post(route('kite.repositories.analyze', $repo))
        ->assertRedirect(route('kite.repositories.structure', $repo))
        ->assertSessionHas('success');

    $repo->refresh();
    expect($repo->isAnalyzed())->toBeTrue();
    expect($repo->total_files_count)->toBe(3); // three blobs
    expect($repo->relevant_files_count)->toBe(2); // vendor/ is ignored
});

it('runs full analysis and flashes an honest unavailable AI result by default', function () {
    $user = User::factory()->withGithub()->create();
    $repo = Repository::factory()->for($user)->create([
        'full_name' => 'octocat/proj',
        'default_branch' => 'main',
    ]);
    fakeRepoTree();

    $response = $this->actingAs($user)->post(route('kite.repositories.full-analysis', $repo));

    $response->assertRedirect(route('kite.repositories.structure', $repo));

    $analysis = session('analysis');
    expect($analysis)->toBeInstanceOf(AnalysisResult::class);
    expect($analysis->success)->toBeFalse(); // NullCodeAnalyzer is the default

    $repo->refresh();
    expect($repo->files_stored)->toBeTrue();
    expect(RepositoryFileTerm::where('user_id', $user->id)->count())->toBeGreaterThan(0);
});

it('forbids viewing another user\'s repository structure', function () {
    $owner = User::factory()->withGithub()->create();
    $repo = Repository::factory()->for($owner)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('kite.repositories.structure', $repo))
        ->assertForbidden();
});

it('forbids analyzing another user\'s repository', function () {
    $owner = User::factory()->withGithub()->create();
    $repo = Repository::factory()->for($owner)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->post(route('kite.repositories.analyze', $repo))
        ->assertForbidden();
});
