<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_clients', function (Blueprint $table): void {
            $table->string('venue_status', 32)->default('active')->after('subscription_tier');
        });

        DB::table('court_clients')->where('is_active', false)->update(['venue_status' => 'inactive']);
        DB::table('court_clients')->where('is_active', true)->update(['venue_status' => 'active']);

        Schema::table('court_clients', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('court_clients', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('subscription_tier');
        });

        DB::table('court_clients')->where('venue_status', 'active')->update(['is_active' => true]);
        DB::table('court_clients')->where('venue_status', '!=', 'active')->update(['is_active' => false]);

        Schema::table('court_clients', function (Blueprint $table): void {
            $table->dropColumn('venue_status');
        });
    }
};
