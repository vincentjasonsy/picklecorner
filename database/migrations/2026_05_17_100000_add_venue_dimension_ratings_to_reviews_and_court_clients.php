<?php

use App\Models\CourtClient;
use App\Services\UserReviewAggregateService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_reviews', function (Blueprint $table): void {
            $table->unsignedTinyInteger('rating_location')->nullable()->after('rating');
            $table->unsignedTinyInteger('rating_amenities')->nullable()->after('rating_location');
            $table->unsignedTinyInteger('rating_price')->nullable()->after('rating_amenities');
        });

        DB::statement(
            "UPDATE user_reviews SET rating_location = rating, rating_amenities = rating, rating_price = rating WHERE target_type = 'venue'"
        );

        Schema::table('court_clients', function (Blueprint $table): void {
            $table->decimal('public_rating_location', 3, 1)->nullable()->after('public_rating_count');
            $table->decimal('public_rating_amenities', 3, 1)->nullable()->after('public_rating_location');
            $table->decimal('public_rating_price', 3, 1)->nullable()->after('public_rating_amenities');
        });

        CourtClient::query()->orderBy('id')->each(function (CourtClient $client): void {
            UserReviewAggregateService::syncVenue($client);
        });
    }

    public function down(): void
    {
        Schema::table('court_clients', function (Blueprint $table): void {
            $table->dropColumn([
                'public_rating_location',
                'public_rating_amenities',
                'public_rating_price',
            ]);
        });

        Schema::table('user_reviews', function (Blueprint $table): void {
            $table->dropColumn([
                'rating_location',
                'rating_amenities',
                'rating_price',
            ]);
        });
    }
};
