<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_venue_credits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('court_client_id')->constrained('court_clients')->cascadeOnDelete();
            $table->unsignedInteger('balance_cents')->default(0);
            $table->string('currency', 8)->default('PHP');
            $table->timestamps();

            $table->unique(['user_id', 'court_client_id']);
        });

        Schema::create('user_venue_credit_ledger_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_venue_credit_id')->constrained('user_venue_credits')->cascadeOnDelete();
            $table->integer('amount_cents');
            $table->unsignedInteger('balance_after_cents');
            $table->string('entry_type', 32);
            $table->uuidMorphs('source');
            $table->string('description', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_venue_credit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_venue_credit_ledger_entries');
        Schema::dropIfExists('user_venue_credits');
    }
};
