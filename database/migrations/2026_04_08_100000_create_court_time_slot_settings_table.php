<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_time_slot_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('court_id')->constrained('courts')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->unsignedTinyInteger('slot_start_hour');
            $table->string('mode', 16);
            $table->unsignedInteger('amount_cents')->nullable();
            $table->timestamps();

            $table->unique(['court_id', 'day_of_week', 'slot_start_hour'], 'ctss_court_day_hour_uq');
            $table->index(['court_id', 'day_of_week'], 'ctss_court_day_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_time_slot_settings');
    }
};
