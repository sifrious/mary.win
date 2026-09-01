<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Every user is a projection of a Zahir account, so the factory mints an
     * opaque account ID rather than a password. A factory that still produced
     * credentials would let tests assert behaviour the application no longer
     * has.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zahir_account_id' => 'acc_'.Str::lower(Str::ulid()),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /** The identity provider asserted an unverified address. */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
