<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\GiftCard;
use App\Models\User;
use App\Services\GiftCardService;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GiftCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_gift_cards_index(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->get(route('admin.gift-cards.index'))->assertOk();
    }

    public function test_super_admin_can_view_gift_card_detail(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $client = CourtClient::factory()->create();
        $card = GiftCardService::issue($client, GiftCard::VALUE_FIXED, 1_000, null, null, null, null, null, 'VIEW-ME', null, null);

        $this->actingAs($super)->get(route('admin.gift-cards.show', $card))->assertOk();
    }

    public function test_platform_wide_gift_card_redeems_at_any_venue(): void
    {
        $a = CourtClient::factory()->create();
        $b = CourtClient::factory()->create();
        $card = GiftCardService::issue(
            null,
            GiftCard::VALUE_FIXED,
            5_000,
            null,
            null,
            null,
            null,
            null,
            'GLOBAL-GIVEBACK',
            null,
            null,
        );

        $this->assertNull($card->court_client_id);

        DB::transaction(function () use ($b, $card): void {
            $debited = GiftCardService::debitForGrossAmount($b->id, $card->code, 1_000);
            $this->assertSame(1_000, $debited['applied_cents']);
        });

        $this->assertSame(5_000, $card->fresh()->balance_cents);

        DB::transaction(function () use ($a, $card): void {
            $debited = GiftCardService::debitForGrossAmount($a->id, $card->code, 500);
            $this->assertSame(500, $debited['applied_cents']);
        });

        $this->assertSame(5_000, $card->fresh()->balance_cents);
    }

    public function test_fixed_gift_card_reusable_full_face_each_time_without_reducing_balance(): void
    {
        $client = CourtClient::factory()->create(['currency' => 'PHP']);
        $card = GiftCardService::issue(
            $client,
            GiftCard::VALUE_FIXED,
            10_000,
            null,
            'Batch',
            'Open day',
            null,
            null,
            'TEST-CODE-1',
            null,
            null,
        );

        $this->assertSame(10_000, $card->fresh()->balance_cents);

        DB::transaction(function () use ($client, $card) {
            $debited = GiftCardService::debitForGrossAmount($client->id, 'test-code-1', 6_000);
            $this->assertSame(6_000, $debited['applied_cents']);

            $booking = Booking::query()->create([
                'court_client_id' => $client->id,
                'court_id' => Court::query()->create([
                    'court_client_id' => $client->id,
                    'name' => 'Court 1',
                    'sort_order' => 0,
                    'environment' => Court::ENV_OUTDOOR,
                ])->id,
                'user_id' => User::factory()->create()->id,
                'starts_at' => now()->addDay(),
                'ends_at' => now()->addDay()->addHour(),
                'status' => Booking::STATUS_CONFIRMED,
                'amount_cents' => 0,
                'currency' => 'PHP',
                'gift_card_id' => $debited['gift_card_id'],
                'gift_card_redeemed_cents' => $debited['applied_cents'],
            ]);

            GiftCardService::recordBookingRedemption($booking, $debited['gift_card_id'], $debited['applied_cents']);
        });

        $this->assertSame(10_000, $card->fresh()->balance_cents);

        DB::transaction(function () use ($client, $card) {
            $debited = GiftCardService::debitForGrossAmount($client->id, 'test-code-1', 6_000);
            $this->assertSame(6_000, $debited['applied_cents']);
        });

        $this->assertSame(10_000, $card->fresh()->balance_cents);
    }

    public function test_debit_fails_for_wrong_venue(): void
    {
        $a = CourtClient::factory()->create();
        $b = CourtClient::factory()->create();
        $card = GiftCardService::issue($a, GiftCard::VALUE_FIXED, 5_000, null, null, null, null, null, 'ONLY-A', null, null);

        $this->expectException(\InvalidArgumentException::class);
        DB::transaction(function () use ($b, $card) {
            GiftCardService::debitForGrossAmount($b->id, $card->code, 1_000);
        });
    }

    public function test_debit_fails_before_valid_from(): void
    {
        $client = CourtClient::factory()->create();
        $card = GiftCardService::issue(
            $client,
            GiftCard::VALUE_FIXED,
            5_000,
            null,
            null,
            null,
            Carbon::now()->addDays(2),
            null,
            'FUTURE',
            null,
            null,
        );

        $this->assertFalse($card->redeemableNow());

        $this->expectException(\InvalidArgumentException::class);
        DB::transaction(function () use ($client, $card) {
            GiftCardService::debitForGrossAmount($client->id, $card->code, 1_000);
        });
    }

    public function test_cancelled_card_cannot_redeem(): void
    {
        $client = CourtClient::factory()->create();
        $card = GiftCardService::issue($client, GiftCard::VALUE_FIXED, 3_000, null, null, null, null, null, 'OFF', null, null);
        $card->cancelled_at = now();
        $card->save();

        $this->expectException(\InvalidArgumentException::class);
        DB::transaction(function () use ($client, $card) {
            GiftCardService::debitForGrossAmount($client->id, $card->code, 500);
        });
    }

    public function test_percent_card_applies_full_percent_each_time_without_reducing_balance(): void
    {
        $client = CourtClient::factory()->create(['currency' => 'PHP']);
        $card = GiftCardService::issue(
            $client,
            GiftCard::VALUE_PERCENT,
            50_000,
            25,
            null,
            null,
            null,
            null,
            'PCT-25',
            null,
            null,
        );

        $this->assertSame(50_000, $card->fresh()->balance_cents);

        DB::transaction(function () use ($client) {
            $debited = GiftCardService::debitForGrossAmount($client->id, 'pct-25', 10_000);
            $this->assertSame(2_500, $debited['applied_cents']);
        });

        $this->assertSame(50_000, $card->fresh()->balance_cents);

        DB::transaction(function () use ($client) {
            $debited = GiftCardService::debitForGrossAmount($client->id, 'pct-25', 10_000);
            $this->assertSame(2_500, $debited['applied_cents']);
        });

        $this->assertSame(50_000, $card->fresh()->balance_cents);
    }

    public function test_debit_fails_when_max_total_redemptions_reached(): void
    {
        $client = CourtClient::factory()->create(['currency' => 'PHP']);
        $user = User::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court 1',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $card = GiftCardService::issue(
            $client,
            GiftCard::VALUE_FIXED,
            5_000,
            null,
            null,
            null,
            null,
            null,
            'ONE-USE-TOTAL',
            null,
            null,
            1,
            null,
        );

        DB::transaction(function () use ($client, $card, $user, $court): void {
            $debited = GiftCardService::debitForGrossAmount($client->id, $card->code, 2_000, $user->id, 1);
            $booking = Booking::query()->create([
                'court_client_id' => $client->id,
                'court_id' => $court->id,
                'user_id' => $user->id,
                'starts_at' => now()->addDay(),
                'ends_at' => now()->addDay()->addHour(),
                'status' => Booking::STATUS_CONFIRMED,
                'amount_cents' => 0,
                'currency' => 'PHP',
                'gift_card_id' => $debited['gift_card_id'],
                'gift_card_redeemed_cents' => $debited['applied_cents'],
            ]);
            GiftCardService::recordBookingRedemption($booking, $debited['gift_card_id'], $debited['applied_cents']);
        });

        $this->assertTrue($card->fresh()->hasReachedTotalRedemptionLimit());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maximum number of uses');
        DB::transaction(function () use ($client, $card, $user): void {
            GiftCardService::debitForGrossAmount($client->id, $card->code, 2_000, $user->id, 1);
        });
    }

    public function test_debit_fails_when_max_per_user_redemptions_reached(): void
    {
        $client = CourtClient::factory()->create(['currency' => 'PHP']);
        $user = User::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court 1',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $card = GiftCardService::issue(
            $client,
            GiftCard::VALUE_FIXED,
            5_000,
            null,
            null,
            null,
            null,
            null,
            'ONE-PER-USER',
            null,
            null,
            null,
            1,
        );

        DB::transaction(function () use ($client, $card, $user, $court): void {
            $debited = GiftCardService::debitForGrossAmount($client->id, $card->code, 2_000, $user->id, 1);
            $booking = Booking::query()->create([
                'court_client_id' => $client->id,
                'court_id' => $court->id,
                'user_id' => $user->id,
                'starts_at' => now()->addDay(),
                'ends_at' => now()->addDay()->addHour(),
                'status' => Booking::STATUS_CONFIRMED,
                'amount_cents' => 0,
                'currency' => 'PHP',
                'gift_card_id' => $debited['gift_card_id'],
                'gift_card_redeemed_cents' => $debited['applied_cents'],
            ]);
            GiftCardService::recordBookingRedemption($booking, $debited['gift_card_id'], $debited['applied_cents']);
        });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maximum uses of this gift card');
        DB::transaction(function () use ($client, $card, $user): void {
            GiftCardService::debitForGrossAmount($client->id, $card->code, 2_000, $user->id, 1);
        });
    }

    public function test_different_users_can_redeem_when_per_user_limit_one(): void
    {
        $client = CourtClient::factory()->create(['currency' => 'PHP']);
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court 1',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $card = GiftCardService::issue(
            $client,
            GiftCard::VALUE_FIXED,
            5_000,
            null,
            null,
            null,
            null,
            null,
            'SHARED-PER-USER',
            null,
            null,
            null,
            1,
        );

        DB::transaction(function () use ($client, $card, $userA, $court): void {
            $debited = GiftCardService::debitForGrossAmount($client->id, $card->code, 1_000, $userA->id, 1);
            $booking = Booking::query()->create([
                'court_client_id' => $client->id,
                'court_id' => $court->id,
                'user_id' => $userA->id,
                'starts_at' => now()->addDay(),
                'ends_at' => now()->addDay()->addHour(),
                'status' => Booking::STATUS_CONFIRMED,
                'amount_cents' => 0,
                'currency' => 'PHP',
                'gift_card_id' => $debited['gift_card_id'],
                'gift_card_redeemed_cents' => $debited['applied_cents'],
            ]);
            GiftCardService::recordBookingRedemption($booking, $debited['gift_card_id'], $debited['applied_cents']);
        });

        DB::transaction(function () use ($client, $card, $userB): void {
            $debited = GiftCardService::debitForGrossAmount($client->id, $card->code, 1_000, $userB->id, 1);
            $this->assertSame(1_000, $debited['applied_cents']);
        });
    }
}
