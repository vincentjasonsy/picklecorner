<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_clients', function (Blueprint $table) {
            $table->string('subscription_tier', 32)->default('basic')->after('admin_user_id');
        });

        DB::table('court_clients')->update(['subscription_tier' => 'premium']);
    }

    public function down(): void
    {
        Schema::table('court_clients', function (Blueprint $table) {
            $table->dropColumn('subscription_tier');
        });
    }
};
