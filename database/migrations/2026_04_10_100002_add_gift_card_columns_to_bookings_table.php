<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignUuid('gift_card_id')->nullable()->after('notes')->constrained('gift_cards')->nullOnDelete();
            $table->unsignedInteger('gift_card_redeemed_cents')->nullable()->after('gift_card_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gift_card_id');
            $table->dropColumn('gift_card_redeemed_cents');
        });
    }
};
