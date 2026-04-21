<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_clients', function (Blueprint $table): void {
            $table->string('booking_notification_email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('court_clients', function (Blueprint $table): void {
            $table->dropColumn('booking_notification_email');
        });
    }
};
