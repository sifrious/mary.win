<?php

namespace App\Console\Commands;

use App\Models\Repository;
use App\Models\User;
use App\Services\RepositoryAnalysisService;
use Illuminate\Console\Command;

class AnalyzeRepositories extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'repositories:analyze
                            {--user= : Analyze repositories for a specific user ID}
                            {--repository= : Analyze a specific repository ID}
                            {--active-only : Only analyze active repositories}
                            {--force : Force re-analysis even if already analyzed}
                            {--dry-run : Show what would be analyzed without actually doing it}';

    /**
     * The console command description.
     */
    protected $description = 'Analyze repository file structures using GitHub Trees API';

    public function __construct(private RepositoryAnalysisService $analysisService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Repository Analysis Tool');
        $this->newLine();

        $repositories = $this->getRepositoriesToAnalyze();

        if ($repositories->isEmpty()) {
            $this->warn('No repositories found to analyze.');

            return Command::SUCCESS;
        }

        $this->info("Found {$repositories->count()} repositories to analyze");

        if ($this->option('dry-run')) {
            $this->showDryRun($repositories);

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($repositories->count());
        $bar->setFormat('verbose');

        $successful = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($repositories as $repository) {
            $bar->advance();

            if (! $this->option('force') && ! $this->analysisService->needsReanalysis($repository)) {
                $this->newLine();
                $this->line("⏭️  Skipping {$repository->full_name} (already analyzed)");
                $skipped++;

                continue;
            }

            $this->newLine();
            $this->line("🔍 Analyzing: {$repository->full_name}");

            try {
                if ($this->analysisService->analyzeRepository($repository)) {
                    $repository->refresh();
                    $this->line("✅ Success: {$repository->relevant_files_count} relevant files found (of {$repository->total_files_count} total)");
                    $successful++;
                } else {
                    $this->line("❌ Failed to analyze {$repository->full_name}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->line("❌ Error analyzing {$repository->full_name}: ".$e->getMessage());
                $failed++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('📊 Analysis Summary');
        $this->table(['Status', 'Count'], [
            ['✅ Successful', $successful],
            ['❌ Failed', $failed],
            ['⏭️ Skipped', $skipped],
            ['📁 Total', $repositories->count()],
        ]);

        if ($failed > 0) {
            $this->warn("⚠️  {$failed} repositories failed to analyze. Check logs for details.");

            return Command::FAILURE;
        }

        $this->info('🎉 Repository analysis completed successfully!');

        return Command::SUCCESS;
    }

    private function getRepositoriesToAnalyze()
    {
        $query = Repository::query();

        if ($userId = $this->option('user')) {
            if (! User::find($userId)) {
                $this->error("User with ID {$userId} not found.");
                exit(1);
            }
            $query->where('user_id', $userId);
        }

        if ($repoId = $this->option('repository')) {
            if (! Repository::find($repoId)) {
                $this->error("Repository with ID {$repoId} not found.");
                exit(1);
            }
            $query->where('id', $repoId);
        }

        if ($this->option('active-only')) {
            $query->where('is_active', true);
        }

        return $query->with('user')->get();
    }

    private function showDryRun($repositories): void
    {
        $this->info('🏃 Dry Run - Repositories that would be analyzed:');
        $this->newLine();

        $tableData = [];
        foreach ($repositories as $repository) {
            $needsAnalysis = $this->option('force') || $this->analysisService->needsReanalysis($repository);

            $tableData[] = [
                $repository->id,
                $repository->full_name,
                $repository->is_active ? 'Active' : 'Inactive',
                $repository->isAnalyzed() ? 'Yes' : 'No',
                $needsAnalysis ? '🔍 Will analyze' : '⏭️ Will skip (already analyzed)',
            ];
        }

        $this->table(['ID', 'Repository', 'Status', 'Analyzed', 'Action'], $tableData);

        $willAnalyze = $repositories->filter(fn ($repo) => $this->option('force') || $this->analysisService->needsReanalysis($repo)
        )->count();

        $this->newLine();
        $this->info("📊 Summary: {$willAnalyze} repositories would be analyzed");
    }
}
