<?php

namespace Database\Factories;

use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'user_type_id' => UserType::query()->where('slug', UserType::SLUG_SUPER_ADMIN)->value('id'),
        ]);
    }

    public function courtAdmin(): static
    {
        return $this->state(fn () => [
            'user_type_id' => UserType::query()->where('slug', UserType::SLUG_COURT_ADMIN)->value('id'),
        ]);
    }

    public function courtClientDesk(?CourtClient $courtClient = null): static
    {
        return $this->state(function () use ($courtClient) {
            $clientId = $courtClient?->id
                ?? CourtClient::query()->value('id');

            return [
                'user_type_id' => UserType::query()->where('slug', UserType::SLUG_COURT_CLIENT_DESK)->value('id'),
                'desk_court_client_id' => $clientId,
            ];
        });
    }

    public function coach(): static
    {
        return $this->state(fn () => [
            'user_type_id' => UserType::query()->where('slug', UserType::SLUG_COACH)->value('id'),
        ]);
    }

    public function player(): static
    {
        return $this->state(fn () => [
            'user_type_id' => UserType::query()->where('slug', UserType::SLUG_USER)->value('id'),
        ]);
    }
}
