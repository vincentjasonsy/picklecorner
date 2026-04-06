<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('open_play_participants', function (Blueprint $table): void {
            $table->string('gcash_reference', 128)->nullable()->after('joiner_note');
        });
    }

    public function down(): void
    {
        Schema::table('open_play_participants', function (Blueprint $table): void {
            $table->dropColumn('gcash_reference');
        });
    }
};
