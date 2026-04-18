<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_courts', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('court_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'court_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_courts');
    }
};
