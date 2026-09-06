<?php

namespace Database\Factories;

use App\Models\RepositoryToRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepositoryToRead>
 */
class RepositoryToReadFactory extends Factory
{
    protected $model = RepositoryToRead::class;

    public function definition(): array
    {
        $owner = fake()->userName();
        $name = fake()->unique()->slug(2);

        return [
            'user_id' => User::factory(),
            'github_id' => (string) fake()->unique()->numberBetween(1, 9_999_999),
            'name' => $name,
            'full_name' => "{$owner}/{$name}",
            'description' => fake()->sentence(),
            'private' => false,
            'url' => "https://github.com/{$owner}/{$name}",
            'default_branch' => 'main',
            'owner' => $owner,
            'stargazers_count' => fake()->numberBetween(0, 5000),
            'forks_count' => fake()->numberBetween(0, 500),
            'language' => fake()->randomElement(['PHP', 'JavaScript', 'Python', 'Go']),
        ];
    }
}
