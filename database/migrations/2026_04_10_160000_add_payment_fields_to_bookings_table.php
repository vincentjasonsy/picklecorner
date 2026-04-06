<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('payment_method', 32)->nullable()->after('gift_card_redeemed_cents');
            $table->string('payment_reference', 128)->nullable()->after('payment_method');
            $table->string('payment_proof_path', 512)->nullable()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_reference', 'payment_proof_path']);
        });
    }
};
