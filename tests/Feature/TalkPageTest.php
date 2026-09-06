<?php

use App\Models\ResearchSource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the nativephp patterns talk page renders', function () {
    $this->get('/talks/nativephp-patterns')
        ->assertOk()
        ->assertSee('Design Patterns', false)
        ->assertSee('in NativePHP')
        ->assertSee('Design patterns under pressure in the NativePHP v4 render cycle')
        ->assertSee('VIEW THE NATIVEPHP DOCS')
        ->assertSee('https://nativephp.com/docs/mobile/4/architecture"', false)
        ->assertDontSee('for Laravel developers')
        ->assertSee('Documented')
        ->assertSee('Interpretation')
        ->assertSee('Claims verified against the live NativePHP v4 docs on 2026-07-30');
});

test('the pattern list renders all nine patterns with their diagrams', function () {
    $response = $this->get('/talks/nativephp-patterns')->assertOk();

    collect([
        'Front controller',
        'Interpreter',
        'Composite',
        'Command',
        'Structural sharing',
        'Producer / consumer',
        'Reconciliation',
        'Bridge',
        'Proxy',
    ])->each(fn (string $pattern) => $response->assertSee($pattern));

    // Ten diagram stages: one per pattern, plus the flipped second Proxy diagram.
    expect(substr_count($response->getContent(), 'class="np-diagram__stage"'))->toBe(10);
});

test('all ten sources link to the exact urls from the citation ledger', function () {
    $response = $this->get('/talks/nativephp-patterns')->assertOk();

    collect([
        'https://nativephp.com/docs/mobile/4/architecture/render-publish-mount',
        'https://nativephp.com/docs/mobile/4/architecture/subtree-reuse',
        'https://nativephp.com/docs/mobile/4/architecture/threading-model',
        'https://nativephp.com/docs/mobile/4/architecture/embedded-php',
        'https://nativephp.com/docs/mobile/4/architecture/cross-platform-implementation',
        'https://nativephp.com/docs/mobile/4/architecture/glossary',
        'https://nativephp.com/docs/mobile/4/architecture/super-native',
        'https://nativephp.com/docs/mobile/4/architecture/about-the-new-architecture',
        'https://nativephp.com/blog/supernative',
        'https://github.com/NativePHP/super-native',
    ])->each(fn (string $url) => $response->assertSee($url, false));
});

test('no private presenter notes leak onto the public page', function () {
    $html = $this->get('/talks/nativephp-patterns')->assertOk()->getContent();

    expect($html)->not->toContain('before you present')
        ->not->toContain('reword')
        ->not->toContain('Q&A')
        ->not->toContain('⚑');
});

test('the home page links the talk from the given list', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('design patterns in nativephp')
        ->assertSee(route('talks.nativephp-patterns'));
});

test('the loved talks index lists every visible loved talk', function () {
    ResearchSource::create([
        'title' => 'Simple Made Easy',
        'author' => 'Rich Hickey',
        'type' => ResearchSource::TYPE_TALK,
        'is_visible' => true,
        'url' => 'https://example.com/simple-made-easy',
        'date_published' => '2011-09-19',
    ]);
    ResearchSource::create([
        'title' => 'A Hidden Talk',
        'type' => ResearchSource::TYPE_TALK,
        'is_visible' => false,
    ]);

    $this->get('/talks/loved')
        ->assertOk()
        ->assertSee('simple made easy')
        ->assertSee('rich hickey · ’11')
        ->assertSee('https://example.com/simple-made-easy')
        ->assertDontSee('a hidden talk');
});

test('the home page links the loved talks index from the plate title', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('talks.loved'));
});
