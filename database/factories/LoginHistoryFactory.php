<?php

namespace Database\Factories;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoginHistory>
 */
class LoginHistoryFactory extends Factory
{
    protected $model = LoginHistory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'attempted_identifier' => fake()->userName(),
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'login_at' => now(),
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LoginHistory::STATUS_SUCCESS,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'status' => LoginHistory::STATUS_FAILED,
        ]);
    }
}
