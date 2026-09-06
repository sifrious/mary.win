<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepositoryToRead extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'github_id',
        'name',
        'full_name',
        'description',
        'private',
        'url',
        'default_branch',
        'owner',
        'stargazers_count',
        'forks_count',
        'language',
    ];

    protected $casts = [
        'private' => 'boolean',
        'stargazers_count' => 'integer',
        'forks_count' => 'integer',
    ];

    /**
     * Get the user that owns this repository
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this reading-list record belongs to the given user.
     *
     * (The source compared against a non-existent `github_username` field; record
     * ownership is what every caller actually needs, so compare user_id.)
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Get repositories for a specific user
     */
    public static function forUser(User $user)
    {
        return static::where('user_id', $user->id)->orderBy('created_at', 'desc');
    }

    /**
     * Get the articles (files) from this repository
     */
    public function articles(): HasMany
    {
        return $this->hasMany(RepositoryArticle::class);
    }

    /**
     * Get the vocabulary terms found in this repository
     */
    public function vocabularyTerms(): HasMany
    {
        return $this->hasMany(ArticleVocabulary::class);
    }
}
