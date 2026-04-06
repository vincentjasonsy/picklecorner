<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_bookings', function (Blueprint $table): void {
            $table->foreignUuid('court_client_invoice_id')->constrained('court_client_invoices')->cascadeOnDelete();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents')->default(0);
            $table->timestamps();

            $table->primary(['court_client_invoice_id', 'booking_id']);
            $table->unique('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_bookings');
    }
};
