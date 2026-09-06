<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin, centralized client for the GitHub REST API.
 *
 * The source app scattered raw Http::withHeaders(...) calls across controllers and
 * services with inconsistent auth schemes ("Bearer …" vs "token …") and missing
 * User-Agent headers. This class is the single place those requests are built, so
 * every call uses one header form (Bearer + a User-Agent, as GitHub recommends).
 *
 * Single-resource getters return the raw Response so callers can branch on status
 * (e.g. 404 vs other errors). repos() follows pagination and returns the merged list.
 */
class GitHubClient
{
    private const BASE = 'https://api.github.com';

    /** Hard cap so a runaway account can't loop forever; 100/page => 5000 repos. */
    private const MAX_REPO_PAGES = 50;

    /**
     * Build a pending request carrying the standard, consistent GitHub headers.
     */
    private function withAuth(string $token): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'Kite-Reader',
            'X-GitHub-Api-Version' => '2022-11-28',
        ]);
    }

    /**
     * Every repository the authenticated user can see, following pagination.
     *
     * Returns the merged array of repo payloads, or null if the very first page
     * failed (so callers can show an error). A later-page failure returns what was
     * collected so far rather than throwing away a good first page.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function repos(string $token): ?array
    {
        $all = [];
        $page = 1;

        while ($page <= self::MAX_REPO_PAGES) {
            $response = $this->withAuth($token)->get(self::BASE.'/user/repos', [
                'per_page' => 100,
                'page' => $page,
                'sort' => 'updated',
                'type' => 'all',
            ]);

            if ($response->failed()) {
                Log::error('GitHub repos fetch failed', ['page' => $page, 'status' => $response->status()]);

                return $page === 1 ? null : $all;
            }

            $batch = $response->json();

            if (! is_array($batch) || $batch === []) {
                break;
            }

            array_push($all, ...$batch);

            // A short page means we've reached the end.
            if (count($batch) < 100) {
                break;
            }

            $page++;
        }

        if ($page > self::MAX_REPO_PAGES) {
            Log::warning('GitHub repos pagination hit the page cap', ['collected' => count($all)]);
        }

        return $all;
    }

    /**
     * The authenticated user (GET /user).
     */
    public function authenticatedUser(string $token): Response
    {
        return $this->withAuth($token)->get(self::BASE.'/user');
    }

    /**
     * A single repository (GET /repos/{owner}/{repo}).
     */
    public function repo(string $token, string $owner, string $name): Response
    {
        return $this->withAuth($token)->get(self::BASE."/repos/{$owner}/{$name}");
    }

    /**
     * A branch, including its head commit SHA (GET /repos/{full_name}/branches/{branch}).
     */
    public function branch(string $token, string $fullName, string $branch): Response
    {
        return $this->withAuth($token)->get(self::BASE."/repos/{$fullName}/branches/{$branch}");
    }

    /**
     * The recursive git tree for a commit/branch SHA
     * (GET /repos/{full_name}/git/trees/{sha}?recursive=1).
     */
    public function tree(string $token, string $fullName, string $sha): Response
    {
        return $this->withAuth($token)->get(self::BASE."/repos/{$fullName}/git/trees/{$sha}", [
            'recursive' => '1',
        ]);
    }

    /**
     * A file's contents (GET /repos/{full_name}/contents/{path}); `content` is base64.
     */
    public function contents(string $token, string $fullName, string $path): Response
    {
        return $this->withAuth($token)->get(self::BASE."/repos/{$fullName}/contents/{$path}");
    }
}
