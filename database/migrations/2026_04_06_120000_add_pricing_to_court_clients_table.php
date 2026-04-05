<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_clients', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hourly_rate_cents')->nullable();
            $table->unsignedInteger('peak_hourly_rate_cents')->nullable();
            $table->string('currency', 3)->default('PHP');
        });
    }

    public function down(): void
    {
        Schema::table('court_clients', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'hourly_rate_cents',
                'peak_hourly_rate_cents',
                'currency',
            ]);
        });
    }
};
