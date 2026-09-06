<?php

use App\Models\Person;
use App\Models\ResearchSource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function talkSource(array $attributes = []): ResearchSource
{
    return ResearchSource::create(array_merge([
        'title' => 'Simple Made Easy',
        'author' => 'Rich Hickey',
        'type' => ResearchSource::TYPE_TALK,
        'is_visible' => true,
        'url' => 'https://example.test/simple-made-easy',
        'date_published' => '2011-01-01',
        'vault_path' => 'clever/bib/simple-made-easy',
    ], $attributes));
}

/**
 * The talk titles rendered inside the LOVED — SEE ALSO plate, in page order.
 *
 * @return array<int, string>
 */
function lovedTitles(string $html): array
{
    $start = strpos($html, 'LOVED — SEE ALSO');

    if ($start === false) {
        return [];
    }

    // The plate ends where the next section (the arcade) begins.
    $end = strpos($html, 'arcade', $start);
    $plate = substr($html, $start, $end === false ? null : $end - $start);

    preg_match_all(
        '/<div class="wr-row">\s*(?:<a[^>]*class="wr-row__name">|<span>)([^<]*)</',
        $plate,
        $matches,
    );

    return $matches[1];
}

test('the loved list shows visible talks, newest first', function () {
    talkSource();
    talkSource([
        'title' => 'Boundaries',
        'author' => 'Gary Bernhardt',
        'url' => 'https://example.test/boundaries',
        'date_published' => '2012-01-01',
        'vault_path' => 'clever/bib/boundaries',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('simple made easy')
        ->assertSee('rich hickey')
        ->assertSee('https://example.test/boundaries')
        ->assertSeeInOrder(['boundaries', 'simple made easy']);
});

test('the loved list is capped at three talks', function () {
    collect(range(1, 8))->each(fn (int $i) => talkSource([
        'title' => "Talk Number {$i}",
        'vault_path' => "clever/bib/talk-{$i}",
    ]));

    $shown = lovedTitles($this->get('/')->assertOk()->getContent());

    expect($shown)->toHaveCount(3);
});

test('the three talks are reshuffled on every load', function () {
    collect(range(1, 8))->each(fn (int $i) => talkSource([
        'title' => "Talk Number {$i}",
        // A shared date so nothing but the shuffle decides who shows up.
        'date_published' => '2015-01-01',
        'vault_path' => "clever/bib/talk-{$i}",
    ]));

    // 8 choose 3 is 56 combinations; ten identical draws would mean the
    // shuffle isn't happening at all.
    $draws = collect(range(1, 10))
        ->map(fn () => lovedTitles($this->get('/')->getContent()))
        ->map(fn (array $titles) => implode('|', $titles))
        ->unique();

    expect($draws->count())->toBeGreaterThan(1);
});

test('the loved list hides sources that are not visible talks', function () {
    talkSource([
        'title' => 'Not Ready Yet',
        'is_visible' => false,
        'vault_path' => 'clever/bib/not-ready',
    ]);
    talkSource([
        'title' => 'An Article, Not A Talk',
        'type' => ResearchSource::TYPE_ARTICLE,
        'vault_path' => 'clever/bib/an-article',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('not ready yet')
        ->assertDontSee('an article, not a talk');
});

test('a talk with no recording renders unlinked', function () {
    talkSource([
        'title' => 'Integration Tests Are A Scam',
        'author' => 'J.B. Rainsberger',
        'url' => null,
        'date_published' => null,
        'vault_path' => 'clever/bib/integration-tests-are-a-scam',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('integration tests are a scam')
        ->assertDontSee('<a href="" class="wr-row__name">', false);
});

test('authors come across from the research library pivot', function () {
    $source = talkSource(['author' => null]);
    $person = Person::create(['slug' => 'rich-hickey', 'name' => 'Rich Hickey']);
    $source->people()->attach($person, ['role' => 'author', 'position' => 0]);

    expect($source->fresh()->people->first()->name)->toBe('Rich Hickey')
        ->and($source->fresh()->people->first()->pivot->role)->toBe('author');
});
