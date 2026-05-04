<?php

namespace App\Services;

use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserVenueCredit;
use App\Models\UserVenueCreditLedgerEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class UserVenueCreditService
{
    /**
     * Add credit (increases balance). Idempotent by transaction + row lock.
     */
    public static function addCredit(
        User $user,
        CourtClient $venue,
        int $amountCents,
        string $entryType,
        Model $source,
        string $description,
    ): void {
        if ($amountCents <= 0) {
            return;
        }

        $currency = $venue->currency ?? 'PHP';

        DB::transaction(function () use ($user, $amountCents, $entryType, $source, $description, $currency): void {
            $row = UserVenueCredit::query()
                ->where('user_id', $user->id)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                $row = UserVenueCredit::query()->create([
                    'user_id' => $user->id,
                    'balance_cents' => 0,
                    'currency' => $currency,
                ]);
                $row = UserVenueCredit::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            }

            $newBalance = $row->balance_cents + $amountCents;
            $row->balance_cents = $newBalance;
            $row->save();

            UserVenueCreditLedgerEntry::query()->create([
                'user_venue_credit_id' => $row->id,
                'amount_cents' => $amountCents,
                'balance_after_cents' => $newBalance,
                'entry_type' => $entryType,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'description' => $description,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Spend venue credit at checkout (decreases balance). Caller must be inside a DB transaction for booking writes.
     */
    public static function debitForCheckout(
        User $user,
        CourtClient $venue,
        int $debitCents,
        Model $source,
        string $description,
    ): void {
        if ($debitCents <= 0) {
            return;
        }

        $currency = $venue->currency ?? 'PHP';

        $row = UserVenueCredit::query()
            ->where('user_id', $user->id)
            ->where('currency', $currency)
            ->lockForUpdate()
            ->first();

        if ($row === null || $row->balance_cents < $debitCents) {
            throw new \InvalidArgumentException('Venue credit balance is insufficient for this checkout.');
        }

        $newBalance = $row->balance_cents - $debitCents;
        $row->balance_cents = $newBalance;
        $row->save();

        UserVenueCreditLedgerEntry::query()->create([
            'user_venue_credit_id' => $row->id,
            'amount_cents' => -$debitCents,
            'balance_after_cents' => $newBalance,
            'entry_type' => UserVenueCreditLedgerEntry::ENTRY_TYPE_CHECKOUT_REDEEM,
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
