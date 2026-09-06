<?php

namespace Database\Factories;

use App\Models\RepositoryFiles;
use App\Models\RepositoryFileTerm;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepositoryFileTerm>
 */
class RepositoryFileTermFactory extends Factory
{
    protected $model = RepositoryFileTerm::class;

    public function definition(): array
    {
        return [
            'repository_file_id' => RepositoryFiles::factory(),
            'term_id' => Term::factory(),
            'user_id' => User::factory(),
            'frequency' => fake()->numberBetween(1, 25),
        ];
    }
}
