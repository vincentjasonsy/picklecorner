<?php

namespace App\Services;

use App\Events\ActivityLogged;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingChangeRequest;
use App\Models\Court;
use App\Models\CourtChangeRequest;
use App\Models\CourtClient;
use App\Models\CourtClientInvoice;
use App\Models\GiftCard;
use App\Models\VenueContactNote;
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
        if ($subject instanceof Booking) {
            return $subject->court_client_id;
        }

        if ($subject instanceof CourtClient) {
            return (string) $subject->getKey();
        }

        if ($subject instanceof Court) {
            return (string) $subject->court_client_id;
        }

        if ($subject instanceof GiftCard) {
            return $subject->court_client_id !== null ? (string) $subject->court_client_id : null;
        }

        if ($subject instanceof CourtChangeRequest) {
            return (string) $subject->court_client_id;
        }

        if ($subject instanceof BookingChangeRequest) {
            return (string) $subject->court_client_id;
        }

        if ($subject instanceof CourtClientInvoice) {
            return (string) $subject->court_client_id;
        }

        if ($subject instanceof VenueContactNote) {
            return (string) $subject->court_client_id;
        }

        return null;
    }
}
