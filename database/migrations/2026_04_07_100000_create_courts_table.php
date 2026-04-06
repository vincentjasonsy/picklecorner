<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('court_client_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('environment', 16)->default('outdoor');
            $table->unsignedInteger('hourly_rate_cents')->nullable();
            $table->unsignedInteger('peak_hourly_rate_cents')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index(['court_client_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
