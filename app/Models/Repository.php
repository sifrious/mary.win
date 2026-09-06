<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repository extends Model
{
    use HasFactory;

    protected $fillable = [
        'github_id',
        'name',
        'full_name',
        'description',
        'private',
        'url',
        'user_id',
        'default_branch',
        'is_active',
        'last_analyzed_commit_sha',
        'file_structure',
        'file_structure_updated_at',
        'total_files_count',
        'relevant_files_count',
        'files_stored',
    ];

    protected $casts = [
        'private' => 'boolean',
        'is_active' => 'boolean',
        'files_stored' => 'boolean',
        'file_structure_updated_at' => 'datetime',
        'total_files_count' => 'integer',
        'relevant_files_count' => 'integer',
        'file_structure' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all files for this repository
     */
    public function files(): HasMany
    {
        return $this->hasMany(RepositoryFiles::class);
    }

    /**
     * Check if the repository has been analyzed
     */
    public function isAnalyzed(): bool
    {
        return ! empty($this->last_analyzed_commit_sha) && ! empty($this->file_structure);
    }

    /**
     * Check if files have been stored in the repository_files table
     */
    public function hasFilesStored(): bool
    {
        return $this->files_stored && $this->files()->exists();
    }

    /**
     * Get files by category
     */
    public function getFilesByCategory(string $category): array
    {
        if ($this->hasFilesStored()) {
            return $this->files()->where('category', $category)->orderBy('path')->get()->toArray();
        }

        // Fallback to JSON structure if files not stored yet
        $structure = $this->file_structure;

        return array_filter($structure, function ($file) use ($category) {
            return ($file['category'] ?? 'other') === $category;
        });
    }

    /**
     * Get README files
     */
    public function getReadmeFiles(): array
    {
        return $this->getFilesByCategory('readme');
    }

    /**
     * Get documentation files
     */
    public function getDocumentationFiles(): array
    {
        return $this->getFilesByCategory('docs');
    }

    /**
     * Get configuration files
     */
    public function getConfigFiles(): array
    {
        return $this->getFilesByCategory('config');
    }

    /**
     * Get the analysis completion percentage
     */
    public function getAnalysisCompletionAttribute(): int
    {
        if (! $this->isAnalyzed()) {
            return 0;
        }

        // Simple metric: if we have structure and counts, consider it 100%
        return 100;
    }
}
