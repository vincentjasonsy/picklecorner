<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repair installs where open_play_shares.user_id was created as an integer column
 * while users.id is UUID — MySQL then truncates UUID strings (SQLSTATE 1265).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('open_play_shares', 'user_id')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $db = DB::getDatabaseName();
        $col = DB::selectOne(
            'SELECT DATA_TYPE, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$db, 'open_play_shares', 'user_id']
        );
        if ($col === null) {
            return;
        }

        $dataType = strtolower((string) ($col->DATA_TYPE ?? ''));
        if ($dataType === 'char') {
            return;
        }

        if (! in_array($dataType, ['bigint', 'int', 'mediumint', 'smallint', 'tinyint'], true)) {
            return;
        }

        try {
            Schema::table('open_play_shares', function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
            });
        } catch (Throwable) {
            // Column may exist without a foreign key name Laravel expects.
        }

        DB::statement('ALTER TABLE `open_play_shares` MODIFY `user_id` CHAR(36) NULL');

        Schema::table('open_play_shares', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Reverting would destroy UUID values; leave schema as-is.
    }
};
