<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->text('open_play_external_contact')->nullable()->after('open_play_host_payment_details');
        });

        Schema::table('open_play_participants', function (Blueprint $table): void {
            $table->string('host_closure_reason', 32)->nullable()->after('gcash_reference');
            $table->text('host_closure_message')->nullable()->after('host_closure_reason');
            $table->timestamp('host_closed_at')->nullable()->after('host_closure_message');
        });
    }

    public function down(): void
    {
        Schema::table('open_play_participants', function (Blueprint $table): void {
            $table->dropColumn(['host_closure_reason', 'host_closure_message', 'host_closed_at']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('open_play_external_contact');
        });
    }
};
