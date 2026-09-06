<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisResult;
use App\Contracts\CodeAnalyzer;
use App\Models\Repository;
use App\Models\RepositoryToRead;
use App\Services\GitHubClient;
use App\Services\ReadingAnalysisService;
use App\Services\RepositoryAnalysisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepositoryController extends Controller
{
    public function __construct(
        private GitHubClient $github,
        private RepositoryAnalysisService $analysisService,
        private CodeAnalyzer $analyzer,
    ) {}

    /**
     * Sync every repository from the user's GitHub account into their own list.
     */
    public function sync(): RedirectResponse
    {
        $repositories = $this->github->repos(auth()->user()->github_token);

        if ($repositories === null) {
            return back()->with('error', 'Failed to fetch repositories from GitHub.');
        }

        foreach ($repositories as $repo) {
            Repository::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'github_id' => (string) $repo['id'],
                ],
                [
                    'name' => $repo['name'],
                    'full_name' => $repo['full_name'],
                    'description' => $repo['description'],
                    'private' => $repo['private'],
                    'url' => $repo['html_url'],
                    'default_branch' => $repo['default_branch'],
                    'is_active' => false,
                ]
            );
        }

        return redirect()->route('kite.repositories.select')
            ->with('success', 'Repositories synchronized successfully! Please select which repositories you\'d like to include.');
    }

    /**
     * Import someone else's public/accessible repository into the reading list.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'repository_url' => 'required|url|regex:/^https:\/\/github\.com\/[a-zA-Z0-9._-]+\/[a-zA-Z0-9._-]+\/?$/',
        ], [
            'repository_url.regex' => 'Please enter a valid GitHub repository URL (e.g., https://github.com/user/repo)',
        ]);

        $url = rtrim($request->repository_url, '/');

        if (! preg_match('/^https:\/\/github\.com\/([a-zA-Z0-9._-]+)\/([a-zA-Z0-9._-]+)$/', $url, $matches)) {
            return back()->with('error', 'Invalid GitHub repository URL format.');
        }

        [$owner, $repoName] = [$matches[1], $matches[2]];
        $token = auth()->user()->github_token;

        $response = $this->github->repo($token, $owner, $repoName);

        if (! $response->successful()) {
            if ($response->status() === 404) {
                return back()->with('error', 'Repository not found. Please check the URL and ensure you have access to this repository.');
            }

            return back()->with('error', 'Failed to fetch repository data from GitHub.');
        }

        $repo = $response->json();

        // Confirm the caller's GitHub identity so we can block importing your own code.
        $userResponse = $this->github->authenticatedUser($token);

        if (! $userResponse->successful()) {
            return back()->with('error', 'Failed to verify user credentials.');
        }

        if (strcasecmp($repo['owner']['login'], $userResponse->json('login')) === 0) {
            return back()->with('error', 'You cannot use kite to read your own code. This repository belongs to you.');
        }

        // Already in the user's own synced repositories?
        if (Repository::where('user_id', auth()->id())->where('github_id', (string) $repo['id'])->exists()) {
            return back()->with('error', 'This repository is already in your collection.');
        }

        // Already in the reading list? Jump straight back to it.
        $existingRepoToRead = RepositoryToRead::where('user_id', auth()->id())
            ->where('github_id', (string) $repo['id'])
            ->first();

        if ($existingRepoToRead) {
            auth()->user()->update(['last_read_repository_to_read_id' => $existingRepoToRead->id]);

            return redirect()->route('kite.reading', ['repository' => $existingRepoToRead->id])
                ->with('success', "Welcome back to '{$repo['name']}' by {$repo['owner']['login']}!");
        }

        $repositoryToRead = RepositoryToRead::create([
            'user_id' => auth()->id(),
            'github_id' => (string) $repo['id'],
            'name' => $repo['name'],
            'full_name' => $repo['full_name'],
            'description' => $repo['description'],
            'private' => $repo['private'],
            'url' => $repo['html_url'],
            'default_branch' => $repo['default_branch'],
            'owner' => $repo['owner']['login'],
            'stargazers_count' => $repo['stargazers_count'],
            'forks_count' => $repo['forks_count'],
            'language' => $repo['language'],
        ]);

        auth()->user()->update(['last_read_repository_to_read_id' => $repositoryToRead->id]);

        return redirect()->route('kite.reading', ['repository' => $repositoryToRead->id])
            ->with('success', "Repository '{$repo['name']}' by {$repo['owner']['login']} has been added to your reading list!");
    }

    /**
     * Show the checkbox list of the user's synced repositories.
     */
    public function select()
    {
        $repositories = auth()->user()->repositories()->orderBy('updated_at', 'desc')->get();

        return view('kite.repositories.select', compact('repositories'));
    }

    /**
     * Activate the chosen repositories (deactivate the rest).
     */
    public function updateSelection(Request $request): RedirectResponse
    {
        $selectedRepos = $request->input('repositories', []);

        auth()->user()->repositories()->update(['is_active' => false]);

        if (! empty($selectedRepos)) {
            auth()->user()->repositories()
                ->whereIn('id', $selectedRepos)
                ->update(['is_active' => true]);
        }

        $count = count($selectedRepos);

        return redirect()->route('kite.dashboard')->with('success', "Successfully activated {$count} repositories!");
    }

    /**
     * Fetch fresh repositories from GitHub, then show the selection list
     * (preserving existing is_active flags).
     */
    public function fetchAndSelect()
    {
        $repositories = $this->github->repos(auth()->user()->github_token);

        if ($repositories === null) {
            return back()->with('error', 'Failed to fetch repositories from GitHub.');
        }

        foreach ($repositories as $repo) {
            Repository::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'github_id' => (string) $repo['id'],
                ],
                [
                    'name' => $repo['name'],
                    'full_name' => $repo['full_name'],
                    'description' => $repo['description'],
                    'private' => $repo['private'],
                    'url' => $repo['html_url'],
                    'default_branch' => $repo['default_branch'],
                    // Don't touch is_active here — preserve the existing selection.
                ]
            );
        }

        $repositories = auth()->user()->repositories()->orderBy('updated_at', 'desc')->get();

        return view('kite.repositories.select', compact('repositories'));
    }

    /**
     * Show a repository's analyzed file structure and extracted vocabulary.
     */
    public function showStructure(Repository $repository)
    {
        $fileStructure = $this->analysisService->getFileStructure($repository);
        $vocabularyTerms = $this->getRepositoryVocabularyTerms($repository);

        return view('kite.repositories.structure', compact('repository', 'fileStructure', 'vocabularyTerms'));
    }

    /**
     * Analyze the repository's file STRUCTURE only (no AI, no term extraction).
     */
    public function analyzeStructure(Repository $repository): RedirectResponse
    {
        if (! $this->analysisService->analyzeRepository($repository)) {
            return redirect()->route('kite.repositories.structure', $repository)
                ->with('error', 'Failed to analyze repository. Please check your GitHub token and try again.');
        }

        return redirect()->route('kite.repositories.structure', $repository)
            ->with('success', 'Repository analyzed successfully! Found '.$repository->fresh()->relevant_files_count.' relevant files.');
    }

    /**
     * Full analysis: structure + terms, then hand off to the AI analyzer.
     */
    public function fullAnalysis(Repository $repository): RedirectResponse
    {
        if (! $this->analysisService->fullAnalysis($repository)) {
            return redirect()->route('kite.repositories.structure', $repository)
                ->with('error', 'Failed to analyze repository. Please check your GitHub token and try again.');
        }

        $repository = $repository->fresh();
        $analysis = $this->analyzeWithAi($repository);

        return redirect()->route('kite.repositories.structure', $repository)
            ->with('success', 'Repository analyzed successfully! Found '.$repository->relevant_files_count.' relevant files and analyzed content.')
            ->with('analysis', $analysis);
    }

    /**
     * Term analysis only, then hand off to the AI analyzer (without file structure).
     */
    public function analyzeTermsOnly(Repository $repository): RedirectResponse
    {
        if (! $this->analysisService->analyzeTermsOnly($repository)) {
            return redirect()->route('kite.repositories.structure', $repository)
                ->with('error', 'Failed to analyze terms. Please check your GitHub token and try again.');
        }

        $analysis = $this->analyzeWithAi($repository->fresh(), ['file_structure' => []]);

        return redirect()->route('kite.repositories.structure', $repository)
            ->with('success', 'Term analysis completed for '.$repository->name.'!')
            ->with('analysis', $analysis);
    }

    /**
     * Extract vocabulary from a reading-list (foreign) repository.
     */
    public function analyzeForReading(RepositoryToRead $repositoryToRead, ReadingAnalysisService $readingAnalysisService): RedirectResponse
    {
        if ($readingAnalysisService->analyzeRepositoryForReading($repositoryToRead)) {
            return redirect()->route('kite.reading', ['repository' => $repositoryToRead->id])
                ->with('success', "Analysis completed for '{$repositoryToRead->name}'! Vocabulary terms have been extracted for learning.");
        }

        return redirect()->route('kite.reading', ['repository' => $repositoryToRead->id])
            ->with('error', 'Failed to analyze repository. Please check your GitHub token and try again.');
    }

    /**
     * Build the analyzer payload and delegate to the swappable CodeAnalyzer.
     *
     * The analyzer never throws for expected failures (see the contract): with no
     * backend configured it returns an honest "unavailable" result, so callers can
     * flash the outcome without a load-bearing try/catch.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function analyzeWithAi(Repository $repository, array $overrides = []): AnalysisResult
    {
        $payload = array_merge([
            'name' => $repository->name,
            'description' => $repository->description,
            'language' => $repository->language,
            'github_url' => $repository->url,
            'file_count' => $repository->total_files_count,
            'relevant_files' => $repository->relevant_files_count,
            'file_structure' => $this->analysisService->getFileStructure($repository),
            'vocabulary_terms' => $this->getRepositoryVocabularyTerms($repository),
        ], $overrides);

        return $this->analyzer->analyze($payload);
    }

    /**
     * Aggregate a repository's extracted vocabulary terms for the word-cloud UI.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRepositoryVocabularyTerms(Repository $repository): array
    {
        $terms = DB::table('repository_file_terms')
            ->join('terms', 'repository_file_terms.term_id', '=', 'terms.id')
            ->join('repository_files', 'repository_file_terms.repository_file_id', '=', 'repository_files.id')
            ->where('repository_files.repository_id', $repository->id)
            ->where('repository_file_terms.user_id', auth()->id())
            ->select([
                'terms.id',
                'terms.function_name',
                'terms.language',
                'terms.framework',
                'terms.implementation',
                'terms.category',
                DB::raw('SUM(repository_file_terms.frequency) as frequency'),
            ])
            ->groupBy('terms.id', 'terms.function_name', 'terms.language', 'terms.framework', 'terms.implementation', 'terms.category')
            ->orderByDesc('frequency')
            ->get();

        return $terms->map(fn ($term) => [
            'id' => $term->id,
            'function_name' => $term->function_name,
            'language' => $term->language ?? 'unknown',
            'framework' => $term->framework,
            'implementation' => $term->implementation,
            'category' => $term->category,
            // The word-cloud component reads both keys; for the user's own code the two are equal.
            'article_frequency' => $term->frequency,
            'user_frequency' => $term->frequency,
        ])->toArray();
    }
}
