<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_change_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('court_client_id')->constrained('court_clients')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('status', 32);
            $table->text('member_note')->nullable();
            $table->dateTime('requested_starts_at')->nullable();
            $table->dateTime('requested_ends_at')->nullable();
            $table->unsignedInteger('offered_credit_cents')->nullable();
            $table->unsignedInteger('resolved_credit_cents')->nullable();
            $table->foreignUuid('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['court_client_id', 'status']);
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_change_requests');
    }
};
