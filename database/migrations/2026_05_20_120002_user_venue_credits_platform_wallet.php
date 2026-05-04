<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_venue_credits')) {
            return;
        }

        if (! Schema::hasColumn('user_venue_credits', 'court_client_id')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $fks = DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = \'user_venue_credits\'
                 AND CONSTRAINT_TYPE = \'FOREIGN KEY\''
            );
            foreach ($fks as $fk) {
                DB::statement('ALTER TABLE user_venue_credits DROP FOREIGN KEY `'.$fk->CONSTRAINT_NAME.'`');
            }
        } else {
            Schema::table('user_venue_credits', function (Blueprint $table): void {
                $table->dropForeign(['court_client_id']);
            });
        }

        // MySQL may use the composite unique as the FK index on `court_client_id`; add a dedicated
        // index so we can drop that unique after removing the foreign key.
        try {
            Schema::table('user_venue_credits', function (Blueprint $table): void {
                $table->index('court_client_id', 'user_venue_credits_court_client_id_index');
            });
        } catch (\Throwable) {
            // Index may already exist from a prior migration attempt.
        }

        try {
            Schema::table('user_venue_credits', function (Blueprint $table): void {
                $table->dropUnique(['user_id', 'court_client_id']);
            });
        } catch (\Throwable) {
            // Unique may already be dropped.
        }

        $pairs = DB::table('user_venue_credits')
            ->select('user_id', 'currency')
            ->distinct()
            ->get();

        foreach ($pairs as $pair) {
            $rows = DB::table('user_venue_credits')
                ->where('user_id', $pair->user_id)
                ->where('currency', $pair->currency)
                ->orderBy('id')
                ->get();

            if ($rows->count() <= 1) {
                continue;
            }

            $keeper = $rows->first();
            $totalBalance = (int) $rows->sum('balance_cents');

            foreach ($rows as $row) {
                if ($row->id === $keeper->id) {
                    continue;
                }
                DB::table('user_venue_credit_ledger_entries')
                    ->where('user_venue_credit_id', $row->id)
                    ->update(['user_venue_credit_id' => $keeper->id]);
                DB::table('user_venue_credits')->where('id', $row->id)->delete();
            }

            DB::table('user_venue_credits')->where('id', $keeper->id)->update([
                'balance_cents' => $totalBalance,
            ]);
        }

        try {
            Schema::table('user_venue_credits', function (Blueprint $table): void {
                $table->unique(['user_id', 'currency']);
            });
        } catch (\Throwable) {
            // Constraint may already exist.
        }

        try {
            Schema::table('user_venue_credits', function (Blueprint $table): void {
                $table->dropIndex('user_venue_credits_court_client_id_index');
            });
        } catch (\Throwable) {
            // Not present (e.g. MySQL) or already dropped.
        }

        Schema::table('user_venue_credits', function (Blueprint $table): void {
            $table->dropColumn('court_client_id');
        });
    }
};
