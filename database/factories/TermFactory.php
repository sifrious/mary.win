<?php

namespace Database\Factories;

use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    protected $model = Term::class;

    public function definition(): array
    {
        return [
            'function_name' => fake()->unique()->word(),
            'language' => fake()->randomElement(['php', 'javascript', 'python']),
            'framework' => null,
            'implementation' => 'user-defined',
            'category' => fake()->randomElement(['database', 'http', 'ui', 'utility', 'general']),
            'library' => null,
            'uses_callback' => false,
        ];
    }
}
