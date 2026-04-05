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
            'password' => 'hashed',
        ];
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
}
