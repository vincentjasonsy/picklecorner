<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_featured_court_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('city', 128);
            $table->foreignUuid('court_client_id')
                ->constrained('court_clients')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['city', 'court_client_id']);
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_featured_court_clients');
    }
};
