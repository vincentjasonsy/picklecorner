<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_client_closed_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('court_client_id')->constrained()->cascadeOnDelete();
            $table->date('closed_on');
            $table->timestamps();

            $table->unique(['court_client_id', 'closed_on'], 'court_client_closed_days_venue_date_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_client_closed_days');
    }
};
