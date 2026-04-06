<?php

namespace App\Policies;

use App\Models\OpenPlaySession;
use App\Models\User;

class OpenPlaySessionPolicy
{
    public function view(User $user, OpenPlaySession $openPlaySession): bool
    {
        return $user->getKey() === $openPlaySession->user_id;
    }

    public function update(User $user, OpenPlaySession $openPlaySession): bool
    {
        return $user->getKey() === $openPlaySession->user_id;
    }

    public function delete(User $user, OpenPlaySession $openPlaySession): bool
    {
        return $user->getKey() === $openPlaySession->user_id;
    }
}
