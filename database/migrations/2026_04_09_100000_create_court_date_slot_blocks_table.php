<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_date_slot_blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('court_id')->constrained('courts')->cascadeOnDelete();
            $table->date('blocked_date');
            $table->unsignedTinyInteger('slot_start_hour');
            $table->timestamps();

            $table->unique(['court_id', 'blocked_date', 'slot_start_hour'], 'cdsb_court_date_hour_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_date_slot_blocks');
    }
};
