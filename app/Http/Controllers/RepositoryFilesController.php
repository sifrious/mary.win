<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use App\Models\RepositoryFiles;
use App\Services\RepositoryFileFrequencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Ownership for every action here is enforced declaratively by the `can:` route
 * middleware (RepositoryPolicy); the nested {file} binding is scoped to {repository}
 * via ->scopeBindings() on the route, so a file from another repo 404s.
 */
class RepositoryFilesController extends Controller
{
    /**
     * "Kite Read": synchronously fetch + term-analyze every relevant file.
     *
     * Kept synchronous (as the source was) so the flow works end-to-end without a
     * queue worker; it is fully exercised in tests via Http::fake().
     */
    public function kiteRead(Repository $repository, RepositoryFileFrequencyService $frequencyService): RedirectResponse
    {
        $frequencyService->analyzeFileFrequency($repository);

        return redirect()->route('kite.repositories.structure', $repository)
            ->with('success', 'Kite Read initiated for '.$repository->name.'!');
    }

    /**
     * Paginated list of a repository's stored files.
     */
    public function index(Repository $repository)
    {
        $files = $repository->files()->orderBy('category')->orderBy('path')->paginate(50);

        return view('kite.repository-files.index', compact('repository', 'files'));
    }

    /**
     * Show a single stored file (with its document content, if fetched).
     */
    public function show(Repository $repository, RepositoryFiles $file)
    {
        return view('kite.repository-files.show', compact('repository', 'file'));
    }

    /**
     * Files in a category (JSON API endpoint).
     */
    public function byCategory(Repository $repository, string $category): JsonResponse
    {
        $files = $repository->files()
            ->where('category', $category)
            ->orderBy('path')
            ->get();

        return response()->json($files);
    }
}
