<?php

use App\Http\Controllers\Games\BrowserGameController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\RepositoryFilesController;
use App\Http\Middleware\ProtectGameAccountResponses;
use App\Livewire\Games\FourLetterWords;
use App\Livewire\Games\LicensePlates;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Models\ArticleVocabulary;
use App\Models\RepositoryArticle;
use App\Models\RepositoryToRead;
use App\Models\ResearchSource;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // TALKS — LOVED comes from the research library shared with clever/landing.
    // Three of them, reshuffled every load, then shown newest first.
    $talksLoved = ResearchSource::query()
        ->lovedTalks()
        ->with('people')
        ->inRandomOrder()
        ->limit(3)
        ->get()
        ->sortByDesc('date_published')
        ->values();

    return view('home', compact('talksLoved'));
})->name('home');

// TALKS — GIVEN: a static page per talk, summary + verified citations.
Route::view('/talks/nativephp-patterns', 'talks.nativephp-patterns')->name('talks.nativephp-patterns');

// TALKS — LOVED: the whole list, not just the three the home page deals out.
Route::get('/talks/loved', function () {
    $talksLoved = ResearchSource::query()
        ->lovedTalks()
        ->orderByDesc('date_published')
        ->orderBy('title')
        ->get();

    return view('talks.loved', compact('talksLoved'));
})->name('talks.loved');

// The OMT symbols reference that previously lived at '/'.
Route::get('/omt', function () {
    return view('welcome');
})->name('omt');

// The arcade.
Route::get('/games/four-letter-words', FourLetterWords::class)->name('games.four-letter-words');
Route::get('/games/license-plates', LicensePlates::class)->name('games.license-plates');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

/*
|--------------------------------------------------------------------------
| Kite — GitHub repository reading tool
|--------------------------------------------------------------------------
| Everything lives under the `kite/` URI prefix and the `kite.` route-name
| prefix so nothing collides with the starter kit's dashboard/home/login.
| Ownership is enforced by `can:` middleware backed by RepositoryPolicy /
| RepositoryToReadPolicy.
*/
Route::prefix('kite')->name('kite.')->group(function () {
    // Public landing / entry point.
    Route::view('/', 'kite.index')->name('index');

    Route::middleware('auth')->group(function () {
        // Simple pages (data is pulled inline via auth()->user() in the views).
        Route::view('dashboard', 'kite.dashboard')->name('dashboard');
        Route::view('code', 'kite.code')->name('code');
        Route::view('onboarding', 'kite.onboarding')->name('onboarding');
        Route::view('terms', 'kite.terms')->name('terms.index');
        Route::view('reading-list', 'kite.reading-list')->name('reading-list');

        // Reading page: track last-read, load vocabulary + paginated articles.
        Route::get('reading/{repository?}', function (?string $repository = null) {
            $user = auth()->user();

            if (! $repository) {
                if ($user->last_read_repository_to_read_id && $user->lastReadRepositoryToRead) {
                    return redirect()->route('kite.reading', $user->last_read_repository_to_read_id);
                }

                return view('kite.reading', [
                    'repositoryToRead' => null,
                    'vocabularyTerms' => [],
                    'repositoryArticles' => null,
                ]);
            }

            $repositoryToRead = RepositoryToRead::where('id', $repository)
                ->where('user_id', $user->id)
                ->first();

            if (! $repositoryToRead) {
                return redirect()->route('kite.reading-list')->with('error', 'Repository not found.');
            }

            $user->update(['last_read_repository_to_read_id' => $repositoryToRead->id]);

            $vocabularyTerms = ArticleVocabulary::getVocabularyForLearning($user, $repositoryToRead, PHP_INT_MAX);
            $repositoryArticles = RepositoryArticle::where('repository_to_read_id', $repositoryToRead->id)
                ->where('user_id', $user->id)
                ->orderBy('path')
                ->paginate(20, ['*'], 'files');

            return view('kite.reading', compact('repositoryToRead', 'vocabularyTerms', 'repositoryArticles'));
        })->name('reading');

        // Own repositories: sync / import / select.
        Route::post('repositories/sync', [RepositoryController::class, 'sync'])->name('repositories.sync');
        Route::post('repositories/import', [RepositoryController::class, 'import'])->name('repositories.import');
        Route::get('repositories/fetch-and-select', [RepositoryController::class, 'fetchAndSelect'])->name('repositories.fetch-and-select');
        Route::get('repositories/select', [RepositoryController::class, 'select'])->name('repositories.select');
        Route::post('repositories/update-selection', [RepositoryController::class, 'updateSelection'])->name('repositories.update-selection');

        // A single repository: structure + analysis (ownership via policy).
        Route::get('repositories/{repository}/structure', [RepositoryController::class, 'showStructure'])
            ->middleware('can:view,repository')->name('repositories.structure');
        Route::post('repositories/{repository}/analyze', [RepositoryController::class, 'analyzeStructure'])
            ->middleware('can:update,repository')->name('repositories.analyze');
        Route::post('repositories/{repository}/full-analysis', [RepositoryController::class, 'fullAnalysis'])
            ->middleware('can:update,repository')->name('repositories.full-analysis');
        Route::post('repositories/{repository}/analyze-terms', [RepositoryController::class, 'analyzeTermsOnly'])
            ->middleware('can:update,repository')->name('repositories.analyze-terms');

        // Reading-list (foreign) repository analysis.
        Route::post('repositories-to-read/{repositoryToRead}/analyze', [RepositoryController::class, 'analyzeForReading'])
            ->middleware('can:update,repositoryToRead')->name('repositories-to-read.analyze');

        // Stored files.
        Route::post('repositories/{repository}/kite-read', [RepositoryFilesController::class, 'kiteRead'])
            ->middleware('can:update,repository')->name('repositories.kite-read');
        Route::get('repositories/{repository}/files', [RepositoryFilesController::class, 'index'])
            ->middleware('can:view,repository')->name('repository-files.index');
        Route::get('repositories/{repository}/files/{file}', [RepositoryFilesController::class, 'show'])
            ->middleware('can:view,repository')->scopeBindings()->name('repository-files.show');
        Route::get('repositories/{repository}/files-by-category/{category}', [RepositoryFilesController::class, 'byCategory'])
            ->middleware('can:view,repository')->name('repository-files.by-category');
    });
});

require __DIR__.'/auth.php';

Route::middleware(['throttle:60,1', ProtectGameAccountResponses::class])->group(function () {
    Route::get('/games/four-letter-words/play', [BrowserGameController::class, 'show'])->block(60, 10)->name('games.flw.play');
    Route::post('/games/four-letter-words/play', [BrowserGameController::class, 'submit'])->block(60, 10)->name('games.flw.submit');
    Route::post('/games/four-letter-words/restart', [BrowserGameController::class, 'restart'])->block(60, 10)->name('games.flw.restart');
    Route::post('/games/four-letter-words/save', [BrowserGameController::class, 'save'])->block(60, 10)->name('games.flw.save');
    Route::get('/games/four-letter-words/saved', [BrowserGameController::class, 'saved'])->block(60, 10)->name('games.flw.saved');
    Route::post('/games/four-letter-words/saved/{run}', [BrowserGameController::class, 'resume'])->whereUuid('run')->block(60, 10)->name('games.flw.resume');
    Route::get('/auth/mary', [BrowserGameController::class, 'login'])->block(60, 10)->name('games.flw.login');
    Route::get('/auth/mary/callback', [BrowserGameController::class, 'callback'])->block(60, 10)->name('games.flw.callback');
    Route::post('/auth/mary/logout', [BrowserGameController::class, 'logout'])->block(60, 10)->name('games.flw.logout');
});
