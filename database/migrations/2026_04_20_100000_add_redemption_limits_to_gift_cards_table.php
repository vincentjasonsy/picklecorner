<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_cards', function (Blueprint $table): void {
            $table->unsignedInteger('max_redemptions_total')->nullable()->after('notes');
            $table->unsignedInteger('max_redemptions_per_user')->nullable()->after('max_redemptions_total');
        });
    }

    public function down(): void
    {
        Schema::table('gift_cards', function (Blueprint $table): void {
            $table->dropColumn(['max_redemptions_total', 'max_redemptions_per_user']);
        });
    }
};
