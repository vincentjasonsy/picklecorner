<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_cards', function (Blueprint $table): void {
            $table->dropForeign(['court_client_id']);
        });

        Schema::table('gift_cards', function (Blueprint $table): void {
            $table->foreignUuid('court_client_id')->nullable()->change();
        });

        Schema::table('gift_cards', function (Blueprint $table): void {
            $table->foreign('court_client_id')
                ->references('id')
                ->on('court_clients')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gift_cards', function (Blueprint $table): void {
            $table->dropForeign(['court_client_id']);
        });

        Schema::table('gift_cards', function (Blueprint $table): void {
            $table->foreignUuid('court_client_id')->nullable(false)->change();
        });

        Schema::table('gift_cards', function (Blueprint $table): void {
            $table->foreign('court_client_id')
                ->references('id')
                ->on('court_clients')
                ->cascadeOnDelete();
        });
    }
};
