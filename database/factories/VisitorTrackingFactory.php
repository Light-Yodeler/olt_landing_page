<?php

namespace Database\Factories;

use App\Models\VisitorTracking;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<VisitorTracking>
 */
class VisitorTrackingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<VisitorTracking>
     */
    protected $model = VisitorTracking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $visitedDate = fake()->dateTimeBetween('-30 days', 'now');
        $firstVisited = Carbon::instance($visitedDate)->setTime(rand(0, 20), rand(0, 59), rand(0, 59));
        $lastVisited = (clone $firstVisited)->addMinutes(rand(0, 180));
        $ip = fake()->boolean(85) ? fake()->ipv4() : fake()->ipv6();
        $userAgent = fake()->userAgent();
        $date = $firstVisited->toDateString();

        $appKey = (string) (config('app.key') ?: 'olt-default-tracking-salt');

        return [
            'visitor_hash' => hash_hmac('sha256', $ip . '|' . $userAgent, $appKey),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'visit_date' => $date,
            'visit_count' => fake()->numberBetween(1, 10),
            'first_visited_at' => $firstVisited,
            'last_visited_at' => $lastVisited,
        ];
    }

    /**
     * State for today's visits.
     */
    public function today(): static
    {
        return $this->state(function (array $attributes) {
            $now = Carbon::now();
            $first = (clone $now)->subMinutes(rand(10, 180));

            return [
                'visit_date' => $now->toDateString(),
                'first_visited_at' => $first,
                'last_visited_at' => $now,
            ];
        });
    }

    /**
     * State for a single visit (visit_count = 1).
     */
    public function singleVisit(): static
    {
        return $this->state(function (array $attributes) {
            $first = isset($attributes['first_visited_at'])
                ? Carbon::parse($attributes['first_visited_at'])
                : Carbon::now();

            return [
                'visit_count' => 1,
                'first_visited_at' => $first,
                'last_visited_at' => $first,
            ];
        });
    }

    /**
     * State for a frequent visitor.
     */
    public function frequentVisitor(int $count = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'visit_count' => $count,
        ]);
    }
}
