<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('open_play_shares', 'user_id')) {
            return;
        }
        Schema::table('open_play_shares', function (Blueprint $table): void {
            $table->foreignUuid('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('open_play_shares', 'user_id')) {
            return;
        }
        Schema::table('open_play_shares', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });
    }
};
