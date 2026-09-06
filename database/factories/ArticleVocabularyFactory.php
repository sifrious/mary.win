<?php

namespace Database\Factories;

use App\Models\ArticleVocabulary;
use App\Models\RepositoryArticle;
use App\Models\RepositoryToRead;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleVocabulary>
 */
class ArticleVocabularyFactory extends Factory
{
    protected $model = ArticleVocabulary::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'repository_to_read_id' => RepositoryToRead::factory(),
            'repository_article_id' => RepositoryArticle::factory(),
            'term_id' => Term::factory(),
            'frequency' => fake()->numberBetween(1, 25),
        ];
    }
}
