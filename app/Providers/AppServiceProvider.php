<?php

namespace App\Providers;

use App\Analysis\HttpCodeAnalyzer;
use App\Analysis\NullCodeAnalyzer;
use App\Contracts\CodeAnalyzer;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The AI analysis backend lives behind this single binding.
        // With no endpoint configured the app runs green on the NullCodeAnalyzer;
        // set services.analyzer.endpoint (Langflow / LangChain / LangGraph) to enable it.
        $this->app->singleton(CodeAnalyzer::class, function ($app) {
            $config = config('services.analyzer');

            if (empty($config['endpoint'])) {
                return new NullCodeAnalyzer;
            }

            return new HttpCodeAnalyzer(
                http: $app->make(Http::class),
                endpoint: $config['endpoint'],
                apiKey: $config['api_key'] ?? null,
                timeout: (int) ($config['timeout'] ?? 60),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
