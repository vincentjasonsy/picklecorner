<?php

namespace App\Policies;

use App\Models\BookingFeeSetting;
use App\Models\User;

class BookingFeeSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, BookingFeeSetting $bookingFeeSetting): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, BookingFeeSetting $bookingFeeSetting): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, BookingFeeSetting $bookingFeeSetting): bool
    {
        return $user->isSuperAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
