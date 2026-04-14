<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_clients', function (Blueprint $table): void {
            $table->text('address')->nullable()->after('city');
            $table->string('phone', 64)->nullable()->after('address');
            $table->string('facebook_url', 512)->nullable()->after('phone');
            $table->decimal('latitude', 10, 7)->nullable()->after('facebook_url');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->json('amenities')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('court_clients', function (Blueprint $table): void {
            $table->dropColumn([
                'address',
                'phone',
                'facebook_url',
                'latitude',
                'longitude',
                'amenities',
            ]);
        });
    }
};
