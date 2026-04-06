<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('court_client_id')->constrained()->cascadeOnDelete();
            $table->string('code', 48)->unique();
            $table->string('title')->nullable();
            $table->string('event_label')->nullable();
            $table->unsignedInteger('face_value_cents');
            $table->unsignedInteger('balance_cents');
            $table->string('currency', 3)->default('PHP');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['court_client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_cards');
    }
};
