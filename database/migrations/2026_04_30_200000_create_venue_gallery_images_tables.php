<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_client_gallery_images', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('court_client_id')->constrained('court_clients')->cascadeOnDelete();
            $table->string('path', 512);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('alt_text', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('court_gallery_images', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('court_id')->constrained('courts')->cascadeOnDelete();
            $table->string('path', 512);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('alt_text', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_gallery_images');
        Schema::dropIfExists('court_client_gallery_images');
    }
};
