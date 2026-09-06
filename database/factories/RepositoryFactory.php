<?php

namespace Database\Factories;

use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Repository>
 */
class RepositoryFactory extends Factory
{
    protected $model = Repository::class;

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
            'is_active' => false,
            'files_stored' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }

    public function analyzed(): static
    {
        return $this->state(fn () => [
            'last_analyzed_commit_sha' => fake()->sha1(),
            'file_structure' => [
                ['path' => 'README.md', 'size' => 1024, 'sha' => fake()->sha1(), 'category' => 'readme'],
                ['path' => 'src/App.php', 'size' => 2048, 'sha' => fake()->sha1(), 'category' => 'code'],
            ],
            'file_structure_updated_at' => now(),
            'total_files_count' => 20,
            'relevant_files_count' => 2,
        ]);
    }
}
