<?php

namespace App\Services;

use App\Models\ArticleVocabulary;
use App\Models\RepositoryArticle;
use App\Models\RepositoryToRead;
use App\Models\Term;
use Illuminate\Support\Facades\Log;

/**
 * The term-extraction engine for FOREIGN repositories the user imports to read
 * (the "reading list"). For each relevant code file it creates a RepositoryArticle,
 * fetches the content, extracts function-name vocabulary, and records it as
 * ArticleVocabulary so the reading UI can surface new-vs-familiar terms.
 *
 * A simpler, parallel engine to RepositoryFileFrequencyService (which handles the
 * user's own repos). Ported from the source; GitHub access via GitHubClient.
 */
class ReadingAnalysisService
{
    public function __construct(private GitHubClient $github) {}

    /**
     * Analyze a repository for reading and vocabulary building.
     */
    public function analyzeRepositoryForReading(RepositoryToRead $repository): bool
    {
        try {
            $token = $repository->user->github_token;

            $fileStructure = $this->getFileStructure($repository, $token);

            if (empty($fileStructure)) {
                Log::error("Failed to get file structure for repository: {$repository->full_name}");

                return false;
            }

            foreach ($this->filterRelevantFiles($fileStructure) as $file) {
                $this->processFileForReading($repository, $file, $token);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error analyzing repository for reading: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Get file structure from the GitHub Trees API.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getFileStructure(RepositoryToRead $repository, string $token): array
    {
        // A branch name is a valid tree-ish, so we can fetch the default branch's tree directly.
        $response = $this->github->tree($token, $repository->full_name, $repository->default_branch);

        if (! $response->successful()) {
            return [];
        }

        return $response->json()['tree'] ?? [];
    }

    /**
     * Filter files to only include relevant code files.
     *
     * @return array<int, array<string, mixed>>
     */
    private function filterRelevantFiles(array $files): array
    {
        $relevantExtensions = ['php', 'js', 'ts', 'py', 'java', 'rb', 'go', 'rs', 'cpp', 'c', 'cs'];
        $excludePatterns = ['test', 'spec', 'vendor', 'node_modules', 'dist', 'build', '.git'];

        return array_filter($files, function ($file) use ($relevantExtensions, $excludePatterns) {
            if ($file['type'] !== 'blob') {
                return false;
            }

            $path = $file['path'];
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            if (! in_array(strtolower($extension), $relevantExtensions)) {
                return false;
            }

            foreach ($excludePatterns as $pattern) {
                if (stripos($path, $pattern) !== false) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Process a single file for reading analysis.
     */
    private function processFileForReading(RepositoryToRead $repository, array $file, string $token): void
    {
        try {
            $article = RepositoryArticle::create([
                'user_id' => $repository->user_id,
                'repository_to_read_id' => $repository->id,
                'path' => $file['path'],
                'filename' => basename($file['path']),
                'file_extension' => pathinfo($file['path'], PATHINFO_EXTENSION),
                'category' => $this->categorizeFile($file['path']),
                'size' => $file['size'] ?? null,
                'sha' => $file['sha'] ?? null,
            ]);

            $content = $this->getFileContent($repository, $file['path'], $token);
            if ($content) {
                $this->analyzeFileTerms($repository, $article, $content);
            }
        } catch (\Exception $e) {
            Log::error("Error processing file {$file['path']}: ".$e->getMessage());
        }
    }

    /**
     * Get file content from the GitHub Contents API.
     */
    private function getFileContent(RepositoryToRead $repository, string $path, string $token): ?string
    {
        $response = $this->github->contents($token, $repository->full_name, $path);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (isset($data['content']) && ($data['encoding'] ?? null) === 'base64') {
            return base64_decode($data['content']);
        }

        return null;
    }

    /**
     * Analyze terms in file content.
     */
    private function analyzeFileTerms(RepositoryToRead $repository, RepositoryArticle $article, string $content): void
    {
        $language = $this->detectLanguage($article->extension);

        foreach ($this->extractTermsFromContent($content, $language) as $functionName => $frequency) {
            $this->storeVocabularyTerm($repository, $article, $functionName, $frequency, $language);
        }
    }

    /**
     * Detect programming language from file extension.
     */
    private function detectLanguage(string $extension): string
    {
        $languageMap = [
            'php' => 'php',
            'js' => 'javascript',
            'ts' => 'typescript',
            'py' => 'python',
            'java' => 'java',
            'rb' => 'ruby',
            'go' => 'go',
            'rs' => 'rust',
            'cpp' => 'cpp',
            'c' => 'c',
            'cs' => 'csharp',
        ];

        return $languageMap[strtolower($extension)] ?? 'unknown';
    }

    /**
     * Extract terms from file content.
     *
     * @return array<string, int>
     */
    private function extractTermsFromContent(string $content, string $language): array
    {
        $terms = [];

        $patterns = [
            'php' => '/([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/',
            'javascript' => '/([a-zA-Z_$][a-zA-Z0-9_$]*)\s*\(/',
            'typescript' => '/([a-zA-Z_$][a-zA-Z0-9_$]*)\s*\(/',
            'python' => '/([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/',
            'java' => '/([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/',
        ];

        $pattern = $patterns[$language] ?? $patterns['php'];

        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $match) {
                $functionName = trim($match);
                if (strlen($functionName) > 2 && ! is_numeric($functionName)) {
                    $terms[$functionName] = ($terms[$functionName] ?? 0) + 1;
                }
            }
        }

        return $terms;
    }

    /**
     * Store a vocabulary term in the database.
     */
    private function storeVocabularyTerm(RepositoryToRead $repository, RepositoryArticle $article, string $functionName, int $frequency, string $language): void
    {
        $term = Term::firstOrCreate([
            'function_name' => $functionName,
            'language' => $language,
        ], [
            'framework' => $this->detectFramework($functionName, $language),
            'category' => $this->categorizeFunction($functionName, $language),
            'implementation' => 'unknown',
        ]);

        ArticleVocabulary::updateOrCreate([
            'user_id' => $repository->user_id,
            'repository_to_read_id' => $repository->id,
            'repository_article_id' => $article->id,
            'term_id' => $term->id,
        ], [
            'frequency' => $frequency,
        ]);
    }

    /**
     * Categorize a file by its path.
     */
    private function categorizeFile(string $path): string
    {
        if (stripos($path, 'controller') !== false) {
            return 'controller';
        }
        if (stripos($path, 'model') !== false) {
            return 'model';
        }
        if (stripos($path, 'view') !== false) {
            return 'view';
        }
        if (stripos($path, 'service') !== false) {
            return 'service';
        }
        if (stripos($path, 'component') !== false) {
            return 'component';
        }
        if (stripos($path, 'util') !== false || stripos($path, 'helper') !== false) {
            return 'utility';
        }
        if (stripos($path, 'config') !== false) {
            return 'configuration';
        }

        return 'other';
    }

    /**
     * Detect framework based on function name and language.
     */
    private function detectFramework(string $functionName, string $language): ?string
    {
        $frameworkPatterns = [
            'laravel' => ['Illuminate', 'Eloquent', 'Schema', 'Route', 'Auth'],
            'react' => ['useState', 'useEffect', 'useContext', 'Component'],
            'vue' => ['createApp', 'reactive', 'ref', 'computed'],
            'express' => ['express', 'router', 'middleware'],
            'django' => ['django', 'models', 'views'],
        ];

        foreach ($frameworkPatterns as $framework => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($functionName, $pattern) !== false) {
                    return $framework;
                }
            }
        }

        return null;
    }

    /**
     * Categorize a function by name.
     */
    private function categorizeFunction(string $functionName, string $language): string
    {
        if (preg_match('/^(find|get|create|update|delete|save|fetch|query|select|insert)/', strtolower($functionName))) {
            return 'database';
        }

        if (preg_match('/^(get|post|put|delete|patch|request|response|fetch|ajax)/', strtolower($functionName))) {
            return 'http';
        }

        if (preg_match('/(auth|login|logout|register|password|token)/', strtolower($functionName))) {
            return 'authentication';
        }

        if (preg_match('/(valid|check|verify|confirm|test)/', strtolower($functionName))) {
            return 'validation';
        }

        return 'general';
    }
}
