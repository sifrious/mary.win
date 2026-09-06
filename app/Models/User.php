<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'github_id',
        'github_token',
        'github_refresh_token',
        'last_read_repository_to_read_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'github_token',
        'github_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // OAuth tokens grant `repo` scope (full private-repo access): encrypt at rest.
            'github_token' => 'encrypted',
            'github_refresh_token' => 'encrypted',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }

    public function repositoriesToRead(): HasMany
    {
        return $this->hasMany(RepositoryToRead::class);
    }

    public function repositoryArticles(): HasMany
    {
        return $this->hasMany(RepositoryArticle::class);
    }

    public function articleVocabulary(): HasMany
    {
        return $this->hasMany(ArticleVocabulary::class);
    }

    /**
     * Get the last read repository to read
     */
    public function lastReadRepositoryToRead(): BelongsTo
    {
        return $this->belongsTo(RepositoryToRead::class, 'last_read_repository_to_read_id');
    }

    /**
     * Get the user's top terms with frequency and language data
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTopTerms(int $limit = 50): array
    {
        return DB::table('repository_file_terms')
            ->join('terms', 'repository_file_terms.term_id', '=', 'terms.id')
            ->where('repository_file_terms.user_id', $this->id)
            ->select([
                'terms.function_name',
                'terms.language',
                'terms.framework',
                'terms.implementation',
                'terms.category',
                DB::raw('SUM(repository_file_terms.frequency) as total_frequency'),
            ])
            ->groupBy('terms.id', 'terms.function_name', 'terms.language', 'terms.framework', 'terms.implementation', 'terms.category')
            ->orderByDesc('total_frequency')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
