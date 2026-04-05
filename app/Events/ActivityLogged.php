<?php

namespace App\Events;

use App\Models\ActivityLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after a row is stored in activity_logs. Listen for in-app notifications, webhooks, etc.
 */
class ActivityLogged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ActivityLog $log) {}
}
