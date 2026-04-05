<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_change_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('court_client_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 32);
            $table->string('environment', 16)->nullable();
            $table->foreignUuid('court_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status', 24)->default('pending');
            $table->foreignUuid('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['court_client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_change_requests');
    }
};
