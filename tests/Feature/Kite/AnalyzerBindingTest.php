<?php

use App\Analysis\HttpCodeAnalyzer;
use App\Analysis\NullCodeAnalyzer;
use App\Contracts\CodeAnalyzer;
use Illuminate\Support\Facades\Http;

it('binds the null analyzer when no endpoint is configured', function () {
    config(['services.analyzer.endpoint' => null]);
    $this->app->forgetInstance(CodeAnalyzer::class);

    $analyzer = app(CodeAnalyzer::class);

    expect($analyzer)->toBeInstanceOf(NullCodeAnalyzer::class);
    expect($analyzer->analyze([])->success)->toBeFalse();
});

it('binds the http analyzer and returns its JSON when an endpoint is configured', function () {
    config([
        'services.analyzer.endpoint' => 'https://ai.test/analyze',
        'services.analyzer.api_key' => null,
        'services.analyzer.timeout' => 5,
    ]);
    $this->app->forgetInstance(CodeAnalyzer::class);

    Http::fake(['ai.test/*' => Http::response(['insight' => 'well-structured'], 200)]);

    $analyzer = app(CodeAnalyzer::class);
    expect($analyzer)->toBeInstanceOf(HttpCodeAnalyzer::class);

    $result = $analyzer->analyze(['name' => 'proj']);
    expect($result->success)->toBeTrue();
    expect($result->data)->toBe(['insight' => 'well-structured']);
});

it('returns unavailable (never throws) when the http backend errors', function () {
    config(['services.analyzer.endpoint' => 'https://ai.test/analyze']);
    $this->app->forgetInstance(CodeAnalyzer::class);

    Http::fake(['ai.test/*' => Http::response([], 500)]);

    $result = app(CodeAnalyzer::class)->analyze(['name' => 'proj']);
    expect($result->success)->toBeFalse();
    expect($result->error)->not->toBeNull();
});
