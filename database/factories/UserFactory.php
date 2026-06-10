<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 *
 * Factory disesuaikan dengan skema tabel users yang telah dimigrasi:
 * user_id, username, password_hash, full_name, role, last_login
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username'      => fake()->unique()->userName(),
            'password_hash' => Hash::make('password'),
            'full_name'     => fake()->name(),
            'role'          => fake()->randomElement(['admin', 'operator', 'viewer']),
            'last_login'    => null,
        ];
    }

    /** State: jadikan user sebagai admin */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /** State: jadikan user sebagai operator */
    public function operator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'operator',
        ]);
    }

    /** State: jadikan user sebagai viewer */
    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'viewer',
        ]);
    }
}
