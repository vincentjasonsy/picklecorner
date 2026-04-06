<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('desk_court_client_id')
                ->nullable()
                ->after('user_type_id')
                ->constrained('court_clients')
                ->nullOnDelete();
        });

        DB::table('user_types')->where('slug', 'coach')->update(['sort_order' => 4]);
        DB::table('user_types')->where('slug', 'user')->update(['sort_order' => 5]);

        DB::table('user_types')->insert([
            'id' => (string) Str::uuid(),
            'slug' => 'court_client_desk',
            'name' => 'Court Client Desk',
            'description' => 'Front-desk staff for a venue; used for future desk workflows.',
            'sort_order' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $deskTypeId = DB::table('user_types')->where('slug', 'court_client_desk')->value('id');
        if ($deskTypeId) {
            $fallbackTypeId = DB::table('user_types')->where('slug', 'user')->value('id');
            if ($fallbackTypeId) {
                DB::table('users')
                    ->where('user_type_id', $deskTypeId)
                    ->update([
                        'user_type_id' => $fallbackTypeId,
                        'desk_court_client_id' => null,
                    ]);
            }
            DB::table('user_types')->where('id', $deskTypeId)->delete();
        }

        DB::table('user_types')->where('slug', 'coach')->update(['sort_order' => 3]);
        DB::table('user_types')->where('slug', 'user')->update(['sort_order' => 4]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('desk_court_client_id');
        });
    }
};
