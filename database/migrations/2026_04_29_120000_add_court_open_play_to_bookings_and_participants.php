<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->boolean('is_open_play')->default(false)->after('coach_fee_cents');
            $table->unsignedTinyInteger('open_play_max_slots')->nullable()->after('is_open_play');
            $table->text('open_play_public_notes')->nullable()->after('open_play_max_slots');
            $table->text('open_play_host_payment_details')->nullable()->after('open_play_public_notes');
        });

        Schema::create('open_play_participants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 24);
            $table->timestamp('paid_at')->nullable();
            $table->text('joiner_note')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'user_id']);
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_play_participants');

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'is_open_play',
                'open_play_max_slots',
                'open_play_public_notes',
                'open_play_host_payment_details',
            ]);
        });
    }
};
