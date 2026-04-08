<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_client_gallery_images', function (Blueprint $table): void {
            $table->timestamp('approved_at')->nullable()->after('alt_text');
        });

        Schema::table('court_gallery_images', function (Blueprint $table): void {
            $table->timestamp('approved_at')->nullable()->after('alt_text');
        });

        $now = now();
        DB::table('court_client_gallery_images')->update(['approved_at' => $now]);
        DB::table('court_gallery_images')->update(['approved_at' => $now]);
    }

    public function down(): void
    {
        Schema::table('court_client_gallery_images', function (Blueprint $table): void {
            $table->dropColumn('approved_at');
        });

        Schema::table('court_gallery_images', function (Blueprint $table): void {
            $table->dropColumn('approved_at');
        });
    }
};
