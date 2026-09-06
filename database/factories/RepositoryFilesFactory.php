<?php

namespace Database\Factories;

use App\Models\Repository;
use App\Models\RepositoryFiles;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepositoryFiles>
 */
class RepositoryFilesFactory extends Factory
{
    protected $model = RepositoryFiles::class;

    public function definition(): array
    {
        $path = fake()->randomElement(['src', 'app', 'lib']).'/'.fake()->unique()->word().'.php';

        return [
            'repository_id' => Repository::factory(),
            'user_id' => User::factory(),
            'path' => $path,
            'filename' => basename($path),
            'file_extension' => 'php',
            'category' => 'code',
            'size' => fake()->numberBetween(100, 20000),
            'sha' => fake()->sha1(),
        ];
    }
}
