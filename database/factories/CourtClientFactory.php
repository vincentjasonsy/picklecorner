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

    public function configure(): static
    {
        return $this->afterMaking(function (CourtClient $client): void {
            if ($client->admin_user_id !== null) {
                return;
            }
            $client->admin_user_id = User::factory()->courtAdmin()->create()->id;
        });
    }

    public function definition(): array
    {
        $name = fake()->company().' Pickleball';

        $avg = round(fake()->randomFloat(1, 35, 50) / 10, 1);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'city' => fake()->city(),
            'notes' => null,
            'admin_user_id' => null,
            'subscription_tier' => CourtClient::TIER_PREMIUM,
            'venue_status' => CourtClient::VENUE_STATUS_ACTIVE,
            'hourly_rate_cents' => fake()->numberBetween(250, 550) * 100,
            'peak_hourly_rate_cents' => null,
            'currency' => 'PHP',
            'cover_image_path' => null,
            'public_rating_average' => $avg,
            'public_rating_count' => fake()->numberBetween(8, 400),
            'public_rating_location' => $avg,
            'public_rating_amenities' => $avg,
            'public_rating_price' => $avg,
        ];
    }

    public function forAdmin(User $user): static
    {
        return $this->state(fn () => [
            'admin_user_id' => $user->id,
        ]);
    }

    public function basicTier(): static
    {
        return $this->state(fn () => [
            'subscription_tier' => CourtClient::TIER_BASIC,
        ]);
    }

    public function premiumTier(): static
    {
        return $this->state(fn () => [
            'subscription_tier' => CourtClient::TIER_PREMIUM,
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn () => [
            'venue_status' => CourtClient::VENUE_STATUS_UPCOMING,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'venue_status' => CourtClient::VENUE_STATUS_INACTIVE,
        ]);
    }
}
