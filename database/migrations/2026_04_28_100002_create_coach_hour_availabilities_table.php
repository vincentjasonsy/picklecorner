<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_hour_availabilities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('coach_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('court_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('hour'); // 0–23, slot start hour in venue TZ
            $table->timestamps();

            $table->unique(['coach_user_id', 'court_id', 'date', 'hour'], 'coach_avail_court_date_hour_unique');
            $table->index(['coach_user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_hour_availabilities');
    }
};
