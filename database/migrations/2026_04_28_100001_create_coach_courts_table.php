<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_courts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('coach_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('court_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['coach_user_id', 'court_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_courts');
    }
};
