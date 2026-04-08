<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_type_id',
        'desk_court_client_id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'demo_expires_at' => 'datetime',
            'internal_team_play_reminders_unsubscribed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Time-limited account created via /try (data removed after {@see $demo_expires_at}). */
    public function isDemoAccount(): bool
    {
        return $this->demo_expires_at !== null;
    }

    public function demoHasExpired(): bool
    {
        return $this->demo_expires_at !== null && now()->greaterThan($this->demo_expires_at);
    }

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    /**
     * When this user is a court admin, the single court client they manage.
     */
    public function administeredCourtClient(): HasOne
    {
        return $this->hasOne(CourtClient::class, 'admin_user_id');
    }

    /**
     * Venue this desk user works at (only for {@see UserType::SLUG_COURT_CLIENT_DESK}).
     */
    public function deskCourtClient(): BelongsTo
    {
        return $this->belongsTo(CourtClient::class, 'desk_court_client_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function venueContactNotesAbout(): HasMany
    {
        return $this->hasMany(VenueContactNote::class, 'user_id')->orderByDesc('created_at');
    }

    public function openPlaySessions(): HasMany
    {
        return $this->hasMany(OpenPlaySession::class);
    }

    public function coachProfile(): HasOne
    {
        return $this->hasOne(CoachProfile::class);
    }

    public function coachedCourts(): HasMany
    {
        return $this->hasMany(CoachCourt::class, 'coach_user_id');
    }

    public function coachHourAvailabilities(): HasMany
    {
        return $this->hasMany(CoachHourAvailability::class, 'coach_user_id');
    }

    /** Bookings where this user is the assigned coach. */
    public function coachedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'coach_user_id');
    }

    /** Court open-play sessions this user joined (as a player, not the booker). */
    public function openPlayParticipations(): HasMany
    {
        return $this->hasMany(OpenPlayParticipant::class, 'user_id');
    }

    /**
     * Distinct venue ids where this coach has at least one court enabled.
     *
     * @return list<string>
     */
    public function coachedCourtClientIds(): array
    {
        $courtIds = $this->coachedCourts()->pluck('court_id');
        if ($courtIds->isEmpty()) {
            return [];
        }

        return Court::query()
            ->whereIn('id', $courtIds)
            ->distinct()
            ->pluck('court_client_id')
            ->all();
    }

    public function isCoach(): bool
    {
        return UserType::query()
            ->where('id', $this->user_type_id)
            ->where('slug', UserType::SLUG_COACH)
            ->exists();
    }

    /** Standard member (player) account — not staff or coach type. */
    public function isPlayer(): bool
    {
        return UserType::query()
            ->where('id', $this->user_type_id)
            ->where('slug', UserType::SLUG_USER)
            ->exists();
    }

    public function isSuperAdmin(): bool
    {
        return UserType::query()
            ->where('id', $this->user_type_id)
            ->where('slug', UserType::SLUG_SUPER_ADMIN)
            ->exists();
    }

    public function isCourtAdmin(): bool
    {
        return UserType::query()
            ->where('id', $this->user_type_id)
            ->where('slug', UserType::SLUG_COURT_ADMIN)
            ->exists();
    }

    public function isCourtClientDesk(): bool
    {
        return UserType::query()
            ->where('id', $this->user_type_id)
            ->where('slug', UserType::SLUG_COURT_CLIENT_DESK)
            ->exists();
    }

    /** Super admin, venue admin, or front desk — use “Go to app” in public site nav. */
    public function usesStaffAppNav(): bool
    {
        return $this->isSuperAdmin() || $this->isCourtAdmin() || $this->isCourtClientDesk();
    }

    /**
     * Entry URL for that staff app, or null for regular members (“My account” uses home).
     */
    public function staffAppHomeUrl(): ?string
    {
        if ($this->isSuperAdmin()) {
            return route('admin.dashboard');
        }
        if ($this->isCourtAdmin()) {
            return route('venue.home');
        }
        if ($this->isCourtClientDesk()) {
            return route('desk.home');
        }

        return null;
    }

    /** Player / coach dashboard on the public site (bookings & profile). */
    public function memberHomeUrl(): string
    {
        return route('account.dashboard');
    }

    /**
     * Distinct venue names for the admin users table. Pass booking venue names when pre-aggregated for the page.
     *
     * @param  list<string>  $bookingVenueNames
     * @return list<string>
     */
    public function associatedVenueNames(array $bookingVenueNames = []): array
    {
        $names = [];

        if ($this->relationLoaded('administeredCourtClient') && $this->administeredCourtClient) {
            $names[] = $this->administeredCourtClient->name;
        }

        if ($this->relationLoaded('deskCourtClient') && $this->deskCourtClient) {
            $names[] = $this->deskCourtClient->name;
        }

        if ($this->relationLoaded('coachedCourts')) {
            foreach ($this->coachedCourts as $row) {
                $n = $row->court?->courtClient?->name;
                if ($n !== null && $n !== '') {
                    $names[] = $n;
                }
            }
        }

        foreach ($bookingVenueNames as $n) {
            if ($n !== null && $n !== '') {
                $names[] = $n;
            }
        }

        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }
}
