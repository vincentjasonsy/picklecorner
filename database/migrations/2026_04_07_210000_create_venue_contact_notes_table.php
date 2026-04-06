<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_contact_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('court_client_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['court_client_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_contact_notes');
    }
};
