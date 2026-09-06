<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleVocabulary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'repository_to_read_id',
        'repository_article_id',
        'term_id',
        'frequency',
    ];

    protected $casts = [
        'frequency' => 'integer',
    ];

    /**
     * Get the user that owns this vocabulary entry
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the repository this vocabulary entry belongs to
     */
    public function repositoryToRead(): BelongsTo
    {
        return $this->belongsTo(RepositoryToRead::class);
    }

    /**
     * Get the article this vocabulary entry belongs to
     */
    public function repositoryArticle(): BelongsTo
    {
        return $this->belongsTo(RepositoryArticle::class);
    }

    /**
     * Get the term this vocabulary entry references
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Get vocabulary terms for learning - prioritizes new terms over familiar ones
     */
    public static function getVocabularyForLearning(User $user, RepositoryToRead $repository, int $limit = 50): array
    {
        // Get all terms from the reading repository
        $vocabularyTerms = \DB::table('article_vocabularies')
            ->join('terms', 'article_vocabularies.term_id', '=', 'terms.id')
            ->where('article_vocabularies.user_id', $user->id)
            ->where('article_vocabularies.repository_to_read_id', $repository->id)
            ->select([
                'terms.id',
                'terms.function_name',
                'terms.language',
                'terms.framework',
                'terms.implementation',
                'terms.category',
                \DB::raw('SUM(article_vocabularies.frequency) as article_frequency'),
            ])
            ->groupBy('terms.id', 'terms.function_name', 'terms.language', 'terms.framework', 'terms.implementation', 'terms.category')
            ->get();

        // Get user's familiarity with each term from their own code
        $userFamiliarity = \DB::table('repository_file_terms')
            ->join('terms', 'repository_file_terms.term_id', '=', 'terms.id')
            ->where('repository_file_terms.user_id', $user->id)
            ->select([
                'terms.id',
                \DB::raw('SUM(repository_file_terms.frequency) as user_frequency'),
            ])
            ->groupBy('terms.id')
            ->pluck('user_frequency', 'id');

        // Combine and categorize terms
        $newTerms = [];
        $familiarTerms = [];

        foreach ($vocabularyTerms as $term) {
            $termData = (array) $term;
            $termData['user_frequency'] = $userFamiliarity[$term->id] ?? 0;

            if ($termData['user_frequency'] == 0) {
                // New terms the user has never used
                $newTerms[] = $termData;
            } else {
                // Terms the user is familiar with
                $familiarTerms[] = $termData;
            }
        }

        // Sort new terms by frequency in the article (most common first)
        usort($newTerms, function ($a, $b) {
            return $b['article_frequency'] - $a['article_frequency'];
        });

        // Sort familiar terms by user's familiarity (least familiar first for learning)
        usort($familiarTerms, function ($a, $b) {
            return $a['user_frequency'] - $b['user_frequency'];
        });

        // Combine: new terms first, then familiar terms
        $result = array_merge($newTerms, $familiarTerms);

        return array_slice($result, 0, $limit);
    }
}
