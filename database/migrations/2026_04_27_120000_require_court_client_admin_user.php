<?php

use App\Models\CourtClient;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_clients', function (Blueprint $table) {
            $table->dropForeign(['admin_user_id']);
        });

        foreach (CourtClient::query()->whereNull('admin_user_id')->cursor() as $client) {
            $admin = User::factory()->courtAdmin()->create();
            $client->forceFill(['admin_user_id' => $admin->id])->saveQuietly();
        }

        Schema::table('court_clients', function (Blueprint $table) {
            $table->foreignUuid('admin_user_id')->nullable(false)->change();
        });

        Schema::table('court_clients', function (Blueprint $table) {
            $table->foreign('admin_user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('court_clients', function (Blueprint $table) {
            $table->dropForeign(['admin_user_id']);
        });

        Schema::table('court_clients', function (Blueprint $table) {
            $table->foreignUuid('admin_user_id')->nullable()->change();
        });

        Schema::table('court_clients', function (Blueprint $table) {
            $table->foreign('admin_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
