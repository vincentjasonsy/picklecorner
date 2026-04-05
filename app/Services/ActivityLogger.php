<?php

namespace App\Services;

use App\Events\ActivityLogged;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function log(
        string $action,
        array $properties = [],
        ?Model $subject = null,
        ?string $description = null,
        ?string $actorUserId = null,
        ?string $courtClientId = null,
    ): ActivityLog {
        $log = ActivityLog::query()->create([
            'user_id' => $actorUserId ?? auth()->id(),
            'court_client_id' => $courtClientId ?? static::inferCourtClientId($subject),
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);

        ActivityLogged::dispatch($log);

        return $log;
    }

    protected static function inferCourtClientId(?Model $subject): ?string
    {
        if ($subject instanceof \App\Models\Booking) {
            return $subject->court_client_id;
        }

        if ($subject instanceof \App\Models\CourtClient) {
            return (string) $subject->getKey();
        }

        if ($subject instanceof \App\Models\Court) {
            return (string) $subject->court_client_id;
        }

        if ($subject instanceof \App\Models\GiftCard) {
            return $subject->court_client_id !== null ? (string) $subject->court_client_id : null;
        }

        if ($subject instanceof \App\Models\CourtChangeRequest) {
            return (string) $subject->court_client_id;
        }

        if ($subject instanceof \App\Models\CourtClientInvoice) {
            return (string) $subject->court_client_id;
        }

        return null;
    }
}
