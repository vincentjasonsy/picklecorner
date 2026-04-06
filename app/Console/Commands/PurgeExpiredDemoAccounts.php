<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PurgeExpiredDemoAccounts extends Command
{
    protected $signature = 'demo:purge-expired-accounts';

    protected $description = 'Delete demo accounts whose trial period has ended (and cascaded member data)';

    public function handle(): int
    {
        $ids = User::query()
            ->whereNotNull('demo_expires_at')
            ->where('demo_expires_at', '<=', now())
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->info('No expired demo accounts to remove.');

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($ids as $id) {
            $user = User::query()->find($id);
            if ($user !== null) {
                $user->delete();
                $count++;
            }
        }

        $this->info("Removed {$count} expired demo account(s).");

        return self::SUCCESS;
    }
}
