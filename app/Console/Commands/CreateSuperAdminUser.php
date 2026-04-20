<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Console\Command;

class CreateSuperAdminUser extends Command
{
    /** Default bootstrap password (override with --password). */
    private const DEFAULT_PASSWORD = 'Sikritu12345-+';

    protected $signature = 'user:create-super-admin
                            {--email=superadmin@picklecorner.ph : Login email}
                            {--name=Super Admin : Display name}
                            {--password= : Password (defaults to built-in bootstrap secret)}
                            {--force : Required in production}';

    protected $description = 'Create or update a super admin user (by email)';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('In production, re-run with --force after confirming this is intentional.');

            return self::FAILURE;
        }

        $superAdminTypeId = UserType::query()->where('slug', UserType::SLUG_SUPER_ADMIN)->value('id');
        if ($superAdminTypeId === null) {
            $this->error('No super_admin user type found. Run migrations and UserTypeSeeder / db:seed first.');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->option('email')));
        $name = trim((string) $this->option('name'));
        $passwordOpt = $this->option('password');
        $password = is_string($passwordOpt) && $passwordOpt !== ''
            ? $passwordOpt
            : self::DEFAULT_PASSWORD;

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name !== '' ? $name : 'Super Admin',
                'password' => $password,
                'user_type_id' => $superAdminTypeId,
                'desk_court_client_id' => null,
                'email_verified_at' => now(),
            ],
        );

        $action = $user->wasRecentlyCreated ? 'Created' : 'Updated';
        $this->info("{$action} super admin: {$user->email}");

        return self::SUCCESS;
    }
}
