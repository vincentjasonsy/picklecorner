<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courts', function (Blueprint $table) {
            $table->timestamp('opens_at')->nullable()->after('is_available');
            $table->timestamp('opening_notice_sent_at')->nullable()->after('opens_at');
        });
    }

    public function down(): void
    {
        Schema::table('courts', function (Blueprint $table) {
            $table->dropColumn(['opens_at', 'opening_notice_sent_at']);
        });
    }
};
