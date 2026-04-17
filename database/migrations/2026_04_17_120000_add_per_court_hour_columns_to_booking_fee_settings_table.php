<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_fee_settings', function (Blueprint $table) {
            $table->string('fee_basis', 32)->default('subtotal')->after('is_active');
            $table->string('per_court_hour_mode', 16)->nullable()->after('fee_basis');
            $table->decimal('per_court_hour_fixed', 8, 2)->nullable()->after('per_court_hour_mode');
            $table->decimal('per_court_hour_percent', 5, 4)->nullable()->after('per_court_hour_fixed');
        });
    }

    public function down(): void
    {
        Schema::table('booking_fee_settings', function (Blueprint $table) {
            $table->dropColumn([
                'fee_basis',
                'per_court_hour_mode',
                'per_court_hour_fixed',
                'per_court_hour_percent',
            ]);
        });
    }
};
