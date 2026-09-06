<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RepositoryFiles extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'user_id',
        'path',
        'filename',
        'file_extension',
        'category',
        'size',
        'sha',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Get the repository that owns this file
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    /**
     * Get the user that owns this file
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the document content for this file
     */
    public function document(): HasOne
    {
        return $this->hasOne(RepositoryFileDocument::class, 'repository_file_id');
    }

    /**
     * Get the terms used in this file
     */
    public function terms(): BelongsToMany
    {
        return $this->belongsToMany(Term::class, 'repository_file_terms')
            ->withPivot(['user_id', 'frequency'])
            ->withTimestamps();
    }

    /**
     * Get files by category
     */
    public static function getByCategory(Repository $repository, string $category): array
    {
        return static::where('repository_id', $repository->id)
            ->where('category', $category)
            ->orderBy('path')
            ->get()
            ->toArray();
    }

    /**
     * Get the file extension
     */
    public function getExtensionAttribute(): string
    {
        // Use stored extension if available, otherwise calculate from filename
        return $this->file_extension ?? pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Get human readable file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get short SHA (first 7 characters)
     */
    public function getShortShaAttribute(): string
    {
        return substr($this->sha, 0, 7);
    }

    /**
     * Check if this file has document content stored
     */
    public function hasDocument(): bool
    {
        return $this->document()->exists();
    }
}
