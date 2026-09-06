<?php

namespace Database\Factories;

use App\Models\RepositoryArticle;
use App\Models\RepositoryToRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepositoryArticle>
 */
class RepositoryArticleFactory extends Factory
{
    protected $model = RepositoryArticle::class;

    public function definition(): array
    {
        $path = 'src/'.fake()->unique()->word().'.php';

        return [
            'user_id' => User::factory(),
            'repository_to_read_id' => RepositoryToRead::factory(),
            'path' => $path,
            'filename' => basename($path),
            'file_extension' => 'php',
            'category' => fake()->randomElement(['controller', 'model', 'service', 'other']),
            'size' => fake()->numberBetween(100, 20000),
            'sha' => fake()->sha1(),
        ];
    }
}
