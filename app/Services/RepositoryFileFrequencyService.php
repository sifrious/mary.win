<?php

namespace App\Services;

use App\Models\Repository;
use App\Models\RepositoryFileDocument;
use App\Models\RepositoryFiles;
use App\Models\RepositoryFileTerm;
use App\Models\Term;
use Illuminate\Support\Facades\Log;

/**
 * The term-extraction engine for the user's OWN repositories.
 *
 * Fetches each relevant file's content from GitHub, stores it as a
 * RepositoryFileDocument, then breaks the code down into individual function/method
 * "terms" (with language, framework, category, callback usage) and records how often
 * each appears via RepositoryFileTerm.
 *
 * Ported from the source engine; GitHub access now goes through GitHubClient (one
 * consistent Bearer + User-Agent header form) and the debug dump() calls are gone.
 * The extraction regexes are preserved as-is, including their known limitations
 * (content is normalized to a single line; a call's parameters are captured only up
 * to the first ")", so deeply nested calls are captured partially).
 */
class RepositoryFileFrequencyService
{
    public function __construct(private GitHubClient $github) {}

    /**
     * Analyze file frequency and patterns for a repository.
     */
    public function analyzeFileFrequency(Repository $repository): void
    {
        // Ensure file_structure is an array (it is cast to array, but be defensive).
        $fileStructure = $repository->file_structure;
        if (is_string($fileStructure)) {
            $fileStructure = json_decode($fileStructure, true);
        }

        if (! is_array($fileStructure)) {
            return;
        }

        foreach ($fileStructure as $fileStructureItem) {
            // Check if this file already exists in the database.
            $existingFile = $repository->files()->where('path', $fileStructureItem['path'])->first();

            if ($existingFile) {
                // Update the file metadata if it has changed.
                $existingFile->update([
                    'size' => $fileStructureItem['size'],
                    'sha' => $fileStructureItem['sha'],
                ]);

                if ($existingFile->hasDocument()) {
                    $fileContentDocument = $this->updateFileContents($fileStructureItem, $repository, $existingFile);

                    if ($fileContentDocument && $existingFile->hasDocument()) {
                        $this->analyzeFileTerms($existingFile, $repository->user_id);
                    }
                } else {
                    $fileContentDocument = $this->getFileContents($fileStructureItem, $repository, $existingFile);

                    if ($fileContentDocument && $existingFile->hasDocument()) {
                        $this->analyzeFileTerms($existingFile, $repository->user_id);
                    }
                }
            } else {
                // Create the repository file record.
                $repositoryFile = RepositoryFiles::create([
                    'repository_id' => $repository->id,
                    'user_id' => $repository->user_id,
                    'path' => $fileStructureItem['path'],
                    'filename' => basename($fileStructureItem['path']),
                    'file_extension' => strtolower(pathinfo($fileStructureItem['path'], PATHINFO_EXTENSION)),
                    'category' => $fileStructureItem['category'],
                    'size' => $fileStructureItem['size'],
                    'sha' => $fileStructureItem['sha'],
                ]);

                $fileContentDocument = $this->getFileContents($fileStructureItem, $repository, $repositoryFile);

                if ($fileContentDocument && $repositoryFile->hasDocument()) {
                    $this->analyzeFileTerms($repositoryFile, $repository->user_id);
                }
            }
        }

        // Mark repository as having files stored.
        $repository->update(['files_stored' => true]);
    }

    /**
     * Fetch a file's content from GitHub and create a new document for it.
     */
    private function getFileContents(array $fileStructureItem, Repository $repository, RepositoryFiles $repositoryFile): ?RepositoryFileDocument
    {
        $token = $repository->user->github_token;

        if (! $token) {
            Log::error("No GitHub token found for user {$repository->user->id}");

            return null;
        }

        $response = $this->github->contents($token, $repository->full_name, $fileStructureItem['path']);

        if (! $response->successful()) {
            Log::error("Failed to fetch file contents for {$fileStructureItem['path']}: ".$response->body());

            return null;
        }

        $data = $response->json();

        // GitHub returns content as base64 encoded.
        if (isset($data['content'])) {
            return RepositoryFileDocument::create([
                'repository_file_id' => $repositoryFile->id,
                'repository_id' => $repository->id,
                'content' => base64_decode($data['content']),
            ]);
        }

        return null;
    }

    /**
     * Re-fetch a file's content from GitHub and update its existing document.
     */
    private function updateFileContents(array $fileStructureItem, Repository $repository, RepositoryFiles $repositoryFile): ?RepositoryFileDocument
    {
        $token = $repository->user->github_token;

        if (! $token) {
            Log::error("No GitHub token found for user {$repository->user->id}");

            return null;
        }

        $response = $this->github->contents($token, $repository->full_name, $fileStructureItem['path']);

        if (! $response->successful()) {
            Log::error("Failed to fetch file contents for {$fileStructureItem['path']}: ".$response->body());

            return null;
        }

        $data = $response->json();

        if (isset($data['content'])) {
            $document = $repositoryFile->document;
            $document->update([
                'content' => base64_decode($data['content']),
            ]);

            return $document;
        }

        return null;
    }

    /**
     * Analyze file content and extract function terms with frequency analysis.
     * This is the core function that breaks down code into individual functions and terms.
     *
     * @return array<string, int>
     */
    public function analyzeFileTerms(RepositoryFiles $repositoryFile, int $userId): array
    {
        if (! $repositoryFile->hasDocument()) {
            return [];
        }

        $content = $repositoryFile->document->content;
        $language = $this->detectLanguage($repositoryFile->extension ?? 'unknown');

        // Clean content - remove comments and normalize whitespace.
        $cleanContent = $this->cleanContent($content);

        // Extract function calls and definitions.
        $functions = $this->extractFunctions($cleanContent, $language);

        // Process each function and store terms.
        $termFrequency = [];

        foreach ($functions as $functionData) {
            $functionName = $functionData['name'];
            $usesCallback = $functionData['uses_callback'];
            $chainedLibrary = $functionData['library'] ?? null;

            $term = Term::findOrCreateTerm([
                'function_name' => $functionName,
                'language' => $language,
                'framework' => $this->detectFramework($functionName, $language),
                'implementation' => $this->detectImplementation($functionName, $language),
                'category' => $this->categorizeFunction($functionName, $language),
                'library' => $chainedLibrary,
                'uses_callback' => $usesCallback,
            ]);

            RepositoryFileTerm::incrementTermFrequency(
                $repositoryFile->id,
                $term->id,
                $userId
            );

            $termFrequency[$functionName] = ($termFrequency[$functionName] ?? 0) + 1;
        }

        // Sort by frequency (most to least).
        arsort($termFrequency);

        return $termFrequency;
    }

    /**
     * Clean content by removing comments, newlines and normalizing whitespace.
     */
    private function cleanContent(string $content): string
    {
        $content = $this->removeComments($content);
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }

    /**
     * Remove comments from code.
     */
    private function removeComments(string $content): string
    {
        // Single-line comments (// and #).
        $content = preg_replace('/(?:\/\/|#).*?(?=\n|$)/m', '', $content);
        // Multi-line comments (/* */).
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);
        // HTML comments.
        $content = preg_replace('/<!--.*?-->/s', '', $content);

        return $content;
    }

    /**
     * Extract function calls and definitions from content.
     *
     * @return array<int, array{name: string, uses_callback: bool, library: ?string}>
     */
    private function extractFunctions(string $content, string $language): array
    {
        return match ($language) {
            'php' => array_merge(
                $this->extractPhpFunctions($content),
                $this->extractPhpMethods($content),
            ),
            'javascript', 'typescript' => array_merge(
                $this->extractJavaScriptFunctions($content),
                $this->extractJavaScriptMethods($content),
            ),
            'python' => $this->extractPythonFunctions($content),
            'java' => $this->extractJavaFunctions($content),
            default => $this->extractGenericFunctions($content),
        };
    }

    /**
     * Extract PHP functions and instance-method calls.
     */
    private function extractPhpFunctions(string $content): array
    {
        $functions = [];

        // Match function calls: functionName(parameters).
        preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $functionName = $match[1];
            $parameters = $match[2];

            if ($this->isPhpKeyword($functionName)) {
                continue;
            }

            $functions[] = [
                'name' => $functionName,
                'uses_callback' => $this->hasCallbackParameter($parameters),
                'library' => $this->extractChainedLibrary($content, $functionName),
            ];
        }

        // Match chained method calls: $object->method().
        preg_match_all('/\$[a-zA-Z_][a-zA-Z0-9_]*->([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $functions[] = [
                'name' => $match[1],
                'uses_callback' => $this->hasCallbackParameter($match[2]),
                'library' => null,
            ];
        }

        return $functions;
    }

    /**
     * Extract PHP static method calls: Class::method().
     */
    private function extractPhpMethods(string $content): array
    {
        $functions = [];

        preg_match_all('/([a-zA-Z_][a-zA-Z0-9_]*)::\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $functions[] = [
                'name' => $match[2],
                'uses_callback' => $this->hasCallbackParameter($match[3]),
                'library' => $match[1],
            ];
        }

        return $functions;
    }

    /**
     * Extract JavaScript/TypeScript function calls and chained methods.
     */
    private function extractJavaScriptFunctions(string $content): array
    {
        $functions = [];

        // Match function calls: functionName(parameters).
        preg_match_all('/\b([a-zA-Z_$][a-zA-Z0-9_$]*)\s*\(([^)]*)\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $functionName = $match[1];

            if ($this->isJavaScriptKeyword($functionName)) {
                continue;
            }

            $functions[] = [
                'name' => $functionName,
                'uses_callback' => $this->hasCallbackParameter($match[2]),
                'library' => $this->extractChainedLibrary($content, $functionName),
            ];
        }

        // Match method chaining: .method().
        preg_match_all('/\.([a-zA-Z_$][a-zA-Z0-9_$]*)\s*\(([^)]*)\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $functions[] = [
                'name' => $match[1],
                'uses_callback' => $this->hasCallbackParameter($match[2]),
                'library' => null,
            ];
        }

        return $functions;
    }

    /**
     * Extract JavaScript object method calls: object.method().
     */
    private function extractJavaScriptMethods(string $content): array
    {
        $functions = [];

        preg_match_all('/([a-zA-Z_$][a-zA-Z0-9_$]*)\.([a-zA-Z_$][a-zA-Z0-9_$]*)\s*\(([^)]*)\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $functions[] = [
                'name' => $match[2],
                'uses_callback' => $this->hasCallbackParameter($match[3]),
                'library' => $match[1],
            ];
        }

        return $functions;
    }

    /**
     * Extract Python function calls and object method calls.
     */
    private function extractPythonFunctions(string $content): array
    {
        $functions = [];

        preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $functionName = $match[1];

            if ($this->isPythonKeyword($functionName)) {
                continue;
            }

            $functions[] = [
                'name' => $functionName,
                'uses_callback' => $this->hasCallbackParameter($match[2]),
                'library' => $this->extractChainedLibrary($content, $functionName),
            ];
        }

        preg_match_all('/([a-zA-Z_][a-zA-Z0-9_]*)\.([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $functions[] = [
                'name' => $match[2],
                'uses_callback' => $this->hasCallbackParameter($match[3]),
                'library' => $match[1],
            ];
        }

        return $functions;
    }

    /**
     * Extract Java method calls: methodName(...) or object.methodName(...).
     */
    private function extractJavaFunctions(string $content): array
    {
        $functions = [];

        preg_match_all('/(?:([a-zA-Z_][a-zA-Z0-9_]*)\.)?([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $methodName = $match[2];

            if ($this->isJavaKeyword($methodName)) {
                continue;
            }

            $functions[] = [
                'name' => $methodName,
                'uses_callback' => $this->hasCallbackParameter($match[3]),
                'library' => $match[1] ?? null,
            ];
        }

        return $functions;
    }

    /**
     * Extract generic function calls for unknown languages.
     */
    private function extractGenericFunctions(string $content): array
    {
        $functions = [];

        preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $functions[] = [
                'name' => $match[1],
                'uses_callback' => $this->hasCallbackParameter($match[2]),
                'library' => null,
            ];
        }

        return $functions;
    }

    /**
     * Check if a call's parameters contain a callback function.
     */
    private function hasCallbackParameter(string $parameters): bool
    {
        return preg_match('/\b(function|=>\s*\{|\([^)]*\)\s*=>\s*\{|function\s*\()/i', $parameters) === 1;
    }

    /**
     * Detect programming language from file extension.
     */
    private function detectLanguage(string $extension): string
    {
        $languageMap = [
            'php' => 'php',
            'js' => 'javascript',
            'jsx' => 'javascript',
            'ts' => 'typescript',
            'tsx' => 'typescript',
            'py' => 'python',
            'java' => 'java',
            'kt' => 'kotlin',
            'swift' => 'swift',
            'rb' => 'ruby',
            'go' => 'go',
            'rs' => 'rust',
            'cpp' => 'cpp',
            'c' => 'c',
            'cs' => 'csharp',
            'html' => 'html',
            'css' => 'css',
            'scss' => 'scss',
            'vue' => 'vue',
            'svelte' => 'svelte',
        ];

        return $languageMap[$extension] ?? 'unknown';
    }

    /**
     * Detect framework based on function name and language.
     */
    private function detectFramework(string $functionName, string $language): ?string
    {
        $frameworks = [
            'php' => [
                'laravel' => ['route', 'view', 'redirect', 'auth', 'config', 'app', 'cache', 'session'],
                'symfony' => ['createForm', 'generateUrl', 'getUser', 'addFlash'],
                'wordpress' => ['wp_query', 'get_posts', 'wp_enqueue_script', 'add_action'],
            ],
            'javascript' => [
                'react' => ['useState', 'useEffect', 'useContext', 'createElement'],
                'vue' => ['ref', 'reactive', 'computed', 'watch'],
                'angular' => ['inject', 'Component', 'Injectable'],
                'jquery' => ['$', 'jQuery'],
            ],
            'python' => [
                'django' => ['render', 'redirect', 'get_object_or_404'],
                'flask' => ['render_template', 'redirect', 'url_for'],
            ],
        ];

        if (isset($frameworks[$language])) {
            foreach ($frameworks[$language] as $framework => $functions) {
                if (in_array($functionName, $functions)) {
                    return $framework;
                }
            }
        }

        return null;
    }

    /**
     * Detect implementation type (built-in vs user-defined).
     */
    private function detectImplementation(string $functionName, string $language): ?string
    {
        $builtIns = [
            'php' => ['array_map', 'array_filter', 'strlen', 'substr', 'preg_match'],
            'javascript' => ['console', 'parseInt', 'parseFloat', 'setTimeout'],
            'python' => ['len', 'str', 'int', 'float', 'print'],
        ];

        if (isset($builtIns[$language]) && in_array($functionName, $builtIns[$language])) {
            return 'built-in';
        }

        return 'user-defined';
    }

    /**
     * Categorize a function by type from its name.
     */
    private function categorizeFunction(string $functionName, string $language): string
    {
        if (preg_match('/\b(find|create|update|delete|save|insert|select|query)\b/i', $functionName)) {
            return 'database';
        }

        if (preg_match('/\b(get|post|put|patch|delete|fetch|ajax|request)\b/i', $functionName)) {
            return 'http';
        }

        if (preg_match('/\b(render|show|hide|toggle|click|hover|focus)\b/i', $functionName)) {
            return 'ui';
        }

        if (preg_match('/\b(map|filter|reduce|sort|format|parse|validate)\b/i', $functionName)) {
            return 'utility';
        }

        return 'general';
    }

    private function isPhpKeyword(string $name): bool
    {
        $keywords = ['if', 'else', 'while', 'for', 'foreach', 'switch', 'case', 'return', 'class', 'function', 'echo', 'print'];

        return in_array(strtolower($name), $keywords);
    }

    private function isJavaScriptKeyword(string $name): bool
    {
        $keywords = ['if', 'else', 'while', 'for', 'switch', 'case', 'return', 'function', 'var', 'let', 'const'];

        return in_array(strtolower($name), $keywords);
    }

    private function isPythonKeyword(string $name): bool
    {
        $keywords = ['if', 'else', 'while', 'for', 'def', 'class', 'return', 'import', 'from', 'print', 'len', 'str', 'int'];

        return in_array(strtolower($name), $keywords);
    }

    private function isJavaKeyword(string $name): bool
    {
        $keywords = ['if', 'else', 'while', 'for', 'switch', 'case', 'return', 'class', 'public', 'private', 'static', 'void'];

        return in_array(strtolower($name), $keywords);
    }

    /**
     * Look for a Class::fn() or object.fn() to attribute a library/class to a plain call.
     */
    private function extractChainedLibrary(string $content, string $functionName): ?string
    {
        if (preg_match('/([a-zA-Z_][a-zA-Z0-9_]*)[:\.]'.preg_quote($functionName, '/').'\s*\(/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
