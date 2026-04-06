<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_clients', function (Blueprint $table): void {
            $table->string('cover_image_path', 512)->nullable()->after('desk_booking_policy');
            $table->decimal('public_rating_average', 3, 1)->nullable()->after('cover_image_path');
            $table->unsignedInteger('public_rating_count')->default(0)->after('public_rating_average');
        });
    }

    public function down(): void
    {
        Schema::table('court_clients', function (Blueprint $table): void {
            $table->dropColumn([
                'cover_image_path',
                'public_rating_average',
                'public_rating_count',
            ]);
        });
    }
};
