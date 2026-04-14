<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_profiles', function (Blueprint $table) {
            $table->decimal('public_rating_average', 3, 1)->nullable()->after('bio');
            $table->unsignedInteger('public_rating_count')->default(0)->after('public_rating_average');
        });
    }

    public function down(): void
    {
        Schema::table('coach_profiles', function (Blueprint $table) {
            $table->dropColumn(['public_rating_average', 'public_rating_count']);
        });
    }
};
