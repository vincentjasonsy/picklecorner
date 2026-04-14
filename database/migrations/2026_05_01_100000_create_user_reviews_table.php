<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('target_type', 16);
            $table->uuid('target_id');
            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();
            $table->string('status', 16)->default('pending');
            $table->boolean('profanity_flag')->default(false);
            $table->foreignUuid('moderated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['target_type', 'target_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_reviews');
    }
};
