<?php

namespace Database\Factories;

use App\Models\CourtClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CourtClient>
 */
class CourtClientFactory extends Factory
{
    protected $model = CourtClient::class;

    public function definition(): array
    {
        $name = fake()->company().' Pickleball';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'city' => fake()->city(),
            'notes' => null,
            'admin_user_id' => null,
            'is_active' => true,
            'hourly_rate_cents' => fake()->numberBetween(250, 550) * 100,
            'peak_hourly_rate_cents' => null,
            'currency' => 'PHP',
        ];
    }

    public function forAdmin(User $user): static
    {
        return $this->state(fn () => [
            'admin_user_id' => $user->id,
        ]);
    }
}
