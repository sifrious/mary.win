<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepositoryFileDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_file_id',
        'repository_id',
        'content',
    ];

    /**
     * Get the repository file that owns this document
     */
    public function repositoryFile(): BelongsTo
    {
        return $this->belongsTo(RepositoryFiles::class, 'repository_file_id');
    }

    /**
     * Get the repository that owns this document
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    /**
     * Get the content length
     */
    public function getContentLengthAttribute(): int
    {
        return strlen($this->content);
    }

    /**
     * Get content preview (first N characters)
     */
    public function getContentPreview(int $length = 200): string
    {
        return substr($this->content, 0, $length);
    }

    /**
     * Get word count
     */
    public function getWordCountAttribute(): int
    {
        return str_word_count($this->content);
    }

    /**
     * Get line count
     */
    public function getLineCountAttribute(): int
    {
        return substr_count($this->content, "\n") + 1;
    }
}
