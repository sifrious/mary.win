<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepositoryArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'repository_to_read_id',
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
     * Get the user that owns this article
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the repository this article belongs to
     */
    public function repositoryToRead(): BelongsTo
    {
        return $this->belongsTo(RepositoryToRead::class);
    }

    /**
     * Get the vocabulary terms found in this article
     */
    public function vocabularyTerms(): HasMany
    {
        return $this->hasMany(ArticleVocabulary::class);
    }

    /**
     * Get the file extension
     */
    public function getExtensionAttribute(): string
    {
        return $this->file_extension ?? pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Get human readable file size
     */
    public function getFormattedSizeAttribute(): string
    {
        if (! $this->size) {
            return 'Unknown';
        }

        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get articles by category for a repository
     */
    public static function getByCategory(RepositoryToRead $repository, string $category): array
    {
        return static::where('repository_to_read_id', $repository->id)
            ->where('category', $category)
            ->orderBy('path')
            ->get()
            ->toArray();
    }
}
