<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use App\Services\CourtSlotPricing;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $clients = CourtClient::query()->get();

        $bookerTypeIds = UserType::query()
            ->whereIn('slug', [UserType::SLUG_USER, UserType::SLUG_COACH])
            ->pluck('id');

        $bookerIds = User::query()
            ->whereIn('user_type_id', $bookerTypeIds)
            ->pluck('id');

        if ($clients->isEmpty() || $bookerIds->isEmpty()) {
            return;
        }

        if (Booking::query()->exists()) {
            return;
        }

        foreach ($clients as $client) {
            for ($i = 0; $i < 25; $i++) {
                $starts = Carbon::parse(\fake()->dateTimeBetween('-120 days', '+14 days'));
                $ends = $starts->copy()->addHours(\fake()->randomElement([1, 2]));

                $status = \fake()->randomElement([
                    Booking::STATUS_CONFIRMED,
                    Booking::STATUS_CONFIRMED,
                    Booking::STATUS_CONFIRMED,
                    Booking::STATUS_COMPLETED,
                    Booking::STATUS_CANCELLED,
                ]);

                $courtModel = Court::query()
                    ->with(['courtClient', 'timeSlotSettings'])
                    ->where('court_client_id', $client->id)
                    ->inRandomOrder()
                    ->first();

                if (! $courtModel) {
                    continue;
                }

                $hourly = CourtSlotPricing::estimatedHourlyCentsAtStart($courtModel, $starts)
                    ?? $client->hourly_rate_cents
                    ?? 30000;
                $amount = (int) round($hourly * \fake()->randomFloat(1, 0.5, 2.5));

                Booking::query()->create([
                    'court_client_id' => $client->id,
                    'court_id' => $courtModel->id,
                    'user_id' => $bookerIds->random(),
                    'starts_at' => $starts,
                    'ends_at' => $ends,
                    'status' => $status,
                    'amount_cents' => $status === Booking::STATUS_CANCELLED ? null : $amount,
                    'currency' => $client->currency ?? 'PHP',
                    'notes' => \fake()->optional(0.2)->sentence(),
                ]);
            }
        }
    }
}
