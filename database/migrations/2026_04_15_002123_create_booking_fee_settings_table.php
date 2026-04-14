<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('base_fee', 8, 2);
            $table->decimal('percentage_fee', 5, 4);
            $table->decimal('max_fee', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_fee_settings');
    }
};
