<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paymongo_booking_intents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('court_client_id')->constrained('court_clients')->cascadeOnDelete();
            $table->unsignedInteger('amount_centavos');
            $table->string('currency', 3)->default('PHP');
            $table->json('payload_json');
            $table->string('paymongo_checkout_session_id')->nullable()->unique();
            $table->string('paymongo_payment_id')->nullable();
            $table->string('status', 32)->default('pending');
            $table->uuid('booking_request_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paymongo_booking_intents');
    }
};
