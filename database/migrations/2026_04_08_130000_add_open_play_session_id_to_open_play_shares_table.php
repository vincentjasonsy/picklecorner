<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('open_play_shares', function (Blueprint $table): void {
            $table->foreignId('open_play_session_id')
                ->nullable()
                ->after('id')
                ->constrained('open_play_sessions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('open_play_shares', function (Blueprint $table): void {
            $table->dropForeign(['open_play_session_id']);
        });
    }
};
