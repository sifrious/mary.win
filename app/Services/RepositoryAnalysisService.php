<?php

namespace App\Services;

use App\Models\Repository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Analyzes a user's OWN repository: fetches its file tree via the GitHub Trees API,
 * filters to the relevant/readable files, categorizes them, and stores the structure
 * on the Repository. Optionally chains term analysis via RepositoryFileFrequencyService.
 *
 * Ported from the source; GitHub access now goes through GitHubClient.
 */
class RepositoryAnalysisService
{
    public function __construct(
        private RepositoryFileFrequencyService $frequencyService,
        private GitHubClient $github,
    ) {}

    /**
     * File extensions we consider relevant for reading/analysis.
     */
    private const RELEVANT_EXTENSIONS = [
        // Documentation
        'md', 'txt', 'rst', 'adoc', 'asciidoc',
        // Code files (most common)
        'js', 'ts', 'jsx', 'tsx', 'vue', 'svelte',
        'php', 'py', 'rb', 'go', 'rs', 'java', 'kt',
        'c', 'cpp', 'h', 'hpp', 'cs', 'swift',
        'html', 'css', 'scss', 'sass', 'less',
        'json', 'xml', 'yaml', 'yml', 'toml',
        // Config files
        'gitignore', 'gitattributes', 'dockerfile',
        'env', 'example', 'config', 'conf',
    ];

    /**
     * Directories to ignore.
     */
    private const IGNORED_DIRECTORIES = [
        'node_modules', 'vendor', '.git', '.svn', '.hg',
        'build', 'dist', 'target', 'bin', 'obj',
        '__pycache__', '.pytest_cache', '.tox',
        'coverage', '.nyc_output', '.coverage',
        'logs', 'temp', 'tmp', '.tmp',
    ];

    /**
     * Analyze a repository's file structure.
     */
    public function analyzeRepository(Repository $repository): bool
    {
        return $this->analyzeRepositoryWithTerms($repository, false);
    }

    /**
     * Analyze a repository's file structure with optional term analysis.
     */
    public function analyzeRepositoryWithTerms(Repository $repository, bool $includeTermAnalysis = false): bool
    {
        try {
            Log::info("Starting analysis for repository: {$repository->full_name}");

            $currentSha = $this->getCurrentCommitSha($repository);

            if (! $currentSha) {
                Log::error("Could not get current commit SHA for {$repository->full_name}");

                return false;
            }

            // Short-circuit if we already have analysis for this exact commit.
            if ($repository->last_analyzed_commit_sha === $currentSha && $repository->file_structure) {
                Log::info("Repository {$repository->full_name} already analyzed for commit {$currentSha}");

                if ($includeTermAnalysis && ! $repository->files_stored) {
                    Log::info("Running term analysis for {$repository->full_name}");
                    $this->frequencyService->analyzeFileFrequency($repository);
                }

                return true;
            }

            $fileStructure = $this->fetchFileStructure($repository, $currentSha);

            if ($fileStructure === null) {
                Log::error("Could not fetch file structure for {$repository->full_name}");

                return false;
            }

            $processedStructure = $this->processFileStructure($fileStructure);

            $repository->update([
                'last_analyzed_commit_sha' => $currentSha,
                'file_structure' => $processedStructure['structure'],
                'file_structure_updated_at' => Carbon::now(),
                'total_files_count' => $processedStructure['total_count'],
                'relevant_files_count' => $processedStructure['relevant_count'],
            ]);

            Log::info("Successfully analyzed {$repository->full_name}: {$processedStructure['total_count']} total files, {$processedStructure['relevant_count']} relevant");

            if ($includeTermAnalysis) {
                Log::info("Running term analysis for {$repository->full_name}");
                $this->frequencyService->analyzeFileFrequency($repository);
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Error analyzing repository {$repository->full_name}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Get the current commit SHA for the default branch.
     */
    private function getCurrentCommitSha(Repository $repository): ?string
    {
        $token = $repository->user->github_token;

        if (! $token) {
            Log::error("No GitHub token found for user {$repository->user->id}");

            return null;
        }

        $response = $this->github->branch($token, $repository->full_name, $repository->default_branch);

        if (! $response->successful()) {
            Log::error("Failed to get current commit SHA for {$repository->full_name}: ".$response->body());

            return null;
        }

        return $response->json('commit.sha');
    }

    /**
     * Fetch file structure using the GitHub Trees API.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function fetchFileStructure(Repository $repository, string $sha): ?array
    {
        $token = $repository->user->github_token;

        $response = $this->github->tree($token, $repository->full_name, $sha);

        if (! $response->successful()) {
            Log::error("Failed to fetch file structure for {$repository->full_name}: ".$response->body());

            return null;
        }

        $data = $response->json();

        if ($data['truncated'] ?? false) {
            Log::warning("File tree was truncated for {$repository->full_name} - repository has more than 100,000 files");
        }

        return $data['tree'] ?? [];
    }

    /**
     * Process and filter the file structure.
     *
     * @return array{structure: array<int, array<string, mixed>>, total_count: int, relevant_count: int}
     */
    private function processFileStructure(array $tree): array
    {
        $relevantFiles = [];
        $totalCount = 0;
        $relevantCount = 0;

        foreach ($tree as $item) {
            if ($item['type'] !== 'blob') {
                continue;
            }

            $totalCount++;
            $path = $item['path'];

            if ($this->isInIgnoredDirectory($path)) {
                continue;
            }

            if ($this->isRelevantFile($path)) {
                $relevantCount++;
                $relevantFiles[] = [
                    'path' => $path,
                    'size' => $item['size'] ?? 0,
                    'sha' => $item['sha'],
                    'category' => $this->categorizeFile($path),
                ];
            }
        }

        usort($relevantFiles, function ($a, $b) {
            $categoryOrder = ['readme' => 0, 'docs' => 1, 'config' => 2, 'code' => 3, 'other' => 4];
            $aCat = $categoryOrder[$a['category']] ?? 4;
            $bCat = $categoryOrder[$b['category']] ?? 4;

            if ($aCat === $bCat) {
                return strcmp($a['path'], $b['path']);
            }

            return $aCat - $bCat;
        });

        return [
            'structure' => $relevantFiles,
            'total_count' => $totalCount,
            'relevant_count' => $relevantCount,
        ];
    }

    /**
     * Check if file is in an ignored directory.
     */
    private function isInIgnoredDirectory(string $path): bool
    {
        foreach (explode('/', $path) as $part) {
            if (in_array($part, self::IGNORED_DIRECTORIES)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a file is relevant for analysis.
     */
    private function isRelevantFile(string $path): bool
    {
        $filename = basename($path);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Always include README, LICENSE, CHANGELOG-style files.
        if (preg_match('/^readme(\.|$)/i', $filename)) {
            return true;
        }

        if (preg_match('/^license(\.|$)/i', $filename)) {
            return true;
        }

        if (preg_match('/^(changelog|changes|history)(\.|$)/i', $filename)) {
            return true;
        }

        $configFiles = [
            'package.json', 'composer.json', 'pyproject.toml', 'cargo.toml',
            'pom.xml', 'build.gradle', 'makefile', 'dockerfile',
        ];

        if (in_array(strtolower($filename), $configFiles)) {
            return true;
        }

        return in_array($extension, self::RELEVANT_EXTENSIONS);
    }

    /**
     * Categorize a file based on its path.
     */
    private function categorizeFile(string $path): string
    {
        $filename = basename($path);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (preg_match('/^readme(\.|$)/i', $filename)) {
            return 'readme';
        }

        if (in_array($extension, ['md', 'txt', 'rst', 'adoc', 'asciidoc']) ||
            stripos($path, 'docs/') !== false ||
            stripos($path, 'doc/') !== false) {
            return 'docs';
        }

        $configExtensions = ['json', 'xml', 'yaml', 'yml', 'toml', 'ini', 'conf', 'config'];
        if (in_array($extension, $configExtensions) ||
            preg_match('/^(package|composer|cargo|pom|build|make|docker)/', strtolower($filename))) {
            return 'config';
        }

        $codeExtensions = [
            'js', 'ts', 'jsx', 'tsx', 'vue', 'svelte',
            'php', 'py', 'rb', 'go', 'rs', 'java', 'kt',
            'c', 'cpp', 'h', 'hpp', 'cs', 'swift',
            'html', 'css', 'scss', 'sass', 'less',
        ];

        if (in_array($extension, $codeExtensions)) {
            return 'code';
        }

        return 'other';
    }

    /**
     * Get file structure for a repository (from cache or fresh analysis).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFileStructure(Repository $repository): array
    {
        if ($repository->file_structure) {
            return $repository->file_structure ?: [];
        }

        if ($this->analyzeRepository($repository)) {
            return $repository->fresh()->file_structure ?: [];
        }

        return [];
    }

    /**
     * Check if repository needs re-analysis (new commits).
     */
    public function needsReanalysis(Repository $repository): bool
    {
        if (! $repository->last_analyzed_commit_sha) {
            return true;
        }

        $currentSha = $this->getCurrentCommitSha($repository);

        return $currentSha && $currentSha !== $repository->last_analyzed_commit_sha;
    }

    /**
     * Run term analysis for a repository that already has structure analyzed.
     */
    public function analyzeTermsOnly(Repository $repository): bool
    {
        try {
            if (! $repository->file_structure) {
                Log::warning("Repository {$repository->full_name} has no file structure - running full analysis first");

                return $this->analyzeRepositoryWithTerms($repository, true);
            }

            Log::info("Running term analysis for {$repository->full_name}");
            $this->frequencyService->analyzeFileFrequency($repository);

            return true;
        } catch (\Exception $e) {
            Log::error("Error analyzing terms for repository {$repository->full_name}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Full analysis: structure + terms in one go.
     */
    public function fullAnalysis(Repository $repository): bool
    {
        return $this->analyzeRepositoryWithTerms($repository, true);
    }
}
