<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'function_name',
        'language',
        'framework',
        'implementation',
        'category',
        'library',
        'uses_callback',
    ];

    protected $casts = [
        'uses_callback' => 'boolean',
    ];

    /**
     * Get repository files that use this term
     */
    public function repositoryFiles(): BelongsToMany
    {
        return $this->belongsToMany(RepositoryFiles::class, 'repository_file_terms')
            ->withPivot(['user_id', 'frequency'])
            ->withTimestamps();
    }

    /**
     * Find or create a term with the given attributes
     */
    public static function findOrCreateTerm(array $attributes): self
    {
        $searchAttributes = [
            'function_name' => $attributes['function_name'],
            'language' => $attributes['language'] ?? null,
            'framework' => $attributes['framework'] ?? null,
            'library' => $attributes['library'] ?? null,
        ];

        $term = static::where($searchAttributes)->first();

        if (! $term) {
            $term = static::create($attributes);
        } else {
            // Update existing term with new information if provided
            $updateData = array_filter([
                'implementation' => $attributes['implementation'] ?? $term->implementation,
                'category' => $attributes['category'] ?? $term->category,
                'uses_callback' => $attributes['uses_callback'] ?? $term->uses_callback,
            ]);

            if (! empty($updateData)) {
                $term->update($updateData);
            }
        }

        return $term;
    }

    /**
     * Get the most frequently used terms across all files
     */
    public static function getMostFrequentTerms(int $limit = 50): array
    {
        return static::select('terms.*')
            ->join('repository_file_terms', 'terms.id', '=', 'repository_file_terms.term_id')
            ->selectRaw('SUM(repository_file_terms.frequency) as total_frequency')
            ->groupBy('terms.id')
            ->orderByDesc('total_frequency')
            ->limit($limit)
            ->get()
            ->mapWithKeys(function ($term) {
                return [$term->function_name => $term->total_frequency];
            })
            ->toArray();
    }
}
