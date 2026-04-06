<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_weekly_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('court_client_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->boolean('is_closed')->default(false);
            $table->string('opens_at', 5)->nullable();
            $table->string('closes_at', 5)->nullable();
            $table->timestamps();

            $table->unique(['court_client_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_weekly_hours');
    }
};
