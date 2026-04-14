<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Database\Factories\CourtClientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Each venue has exactly one court admin user ({@see $admin_user_id}); each court admin manages at most one venue.
 *
 * @property string $admin_user_id
 */
class CourtClient extends Model
{
    /** @use HasFactory<CourtClientFactory> */
    use HasFactory, HasUuids;

    public const DESK_BOOKING_POLICY_MANUAL = 'manual';

    public const DESK_BOOKING_POLICY_AUTO_APPROVE = 'auto_approve';

    public const DESK_BOOKING_POLICY_AUTO_DENY = 'auto_deny';

    /** @see self::subscriptionTierValues() — manual booking, desk queue, courts, reports, settings */
    public const TIER_BASIC = 'basic';

    /** Gift cards + customer CRM (and anything else marked Premium-only) */
    public const TIER_PREMIUM = 'premium';

    protected $fillable = [
        'name',
        'slug',
        'city',
        'notes',
        'admin_user_id',
        'subscription_tier',
        'is_active',
        'hourly_rate_cents',
        'peak_hourly_rate_cents',
        'currency',
        'desk_booking_policy',
        'cover_image_path',
        'public_rating_average',
        'public_rating_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'hourly_rate_cents' => 'integer',
            'peak_hourly_rate_cents' => 'integer',
            'public_rating_average' => 'decimal:1',
            'public_rating_count' => 'integer',
        ];
    }

    public function coverImageUrl(): ?string
    {
        if ($this->cover_image_path === null || $this->cover_image_path === '') {
            return null;
        }

        return PublicStorageUrl::forPath($this->cover_image_path);
    }

    /**
     * Venue booking page / marketing carousel: gallery rows first, else legacy single cover.
     *
     * @return list<array{src: string, alt: string}>
     */
    public function carouselSlides(): array
    {
        $images = $this->relationLoaded('approvedGalleryImages')
            ? $this->approvedGalleryImages
            : $this->approvedGalleryImages()->get();

        $slides = [];
        foreach ($images as $img) {
            $slides[] = [
                'src' => $img->publicUrl(),
                'alt' => (string) ($img->alt_text ?: $this->name),
            ];
        }

        if ($slides === []) {
            $cover = $this->coverImageUrl();
            if ($cover !== null) {
                $slides[] = ['src' => $cover, 'alt' => $this->name];
            }
        }

        return $slides;
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class)->orderBy('sort_order');
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(CourtClientGalleryImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Shown on public booking page / carousels only after super-admin approval. */
    public function approvedGalleryImages(): HasMany
    {
        return $this->galleryImages()->whereNotNull('approved_at');
    }

    public function weeklyHours(): HasMany
    {
        return $this->hasMany(VenueWeeklyHour::class)->orderBy('day_of_week');
    }

    /** Whole-venue calendar closures (holidays, etc.) — no public bookings that local calendar day. */
    public function closedDays(): HasMany
    {
        return $this->hasMany(CourtClientClosedDay::class)->orderBy('closed_on');
    }

    public function isClosedOnDate(string $dateYmd): bool
    {
        return $this->closedDays()->whereDate('closed_on', $dateYmd)->exists();
    }

    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class)->orderByDesc('created_at');
    }

    public function deskUsers(): HasMany
    {
        return $this->hasMany(User::class, 'desk_court_client_id')
            ->whereHas('userType', fn ($q) => $q->where('slug', UserType::SLUG_COURT_CLIENT_DESK));
    }

    public function courtChangeRequests(): HasMany
    {
        return $this->hasMany(CourtChangeRequest::class)->orderByDesc('created_at');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CourtClientInvoice::class)->orderByDesc('created_at');
    }

    public function venueContactNotes(): HasMany
    {
        return $this->hasMany(VenueContactNote::class)->orderByDesc('created_at');
    }

    /** @return list<string> */
    public static function deskBookingPolicyValues(): array
    {
        return [
            self::DESK_BOOKING_POLICY_MANUAL,
            self::DESK_BOOKING_POLICY_AUTO_APPROVE,
            self::DESK_BOOKING_POLICY_AUTO_DENY,
        ];
    }

    public function deskBookingPolicyHelpText(): string
    {
        $policy = in_array((string) ($this->desk_booking_policy ?? ''), self::deskBookingPolicyValues(), true)
            ? (string) $this->desk_booking_policy
            : self::DESK_BOOKING_POLICY_MANUAL;

        return match ($policy) {
            self::DESK_BOOKING_POLICY_AUTO_APPROVE => 'This venue confirms desk requests automatically.',
            self::DESK_BOOKING_POLICY_AUTO_DENY => 'This venue automatically declines desk requests.',
            default => 'A venue admin approves or denies each desk request.',
        };
    }

    public function deskBookingPolicyNormalized(): string
    {
        $p = (string) ($this->desk_booking_policy ?? '');

        return in_array($p, self::deskBookingPolicyValues(), true)
            ? $p
            : self::DESK_BOOKING_POLICY_MANUAL;
    }

    /** Short label for venue admin UI (nav, cards, banners). */
    public function deskBookingPolicyShortLabel(): string
    {
        return match ($this->deskBookingPolicyNormalized()) {
            self::DESK_BOOKING_POLICY_AUTO_APPROVE => 'Auto-confirm',
            self::DESK_BOOKING_POLICY_AUTO_DENY => 'Auto-deny',
            default => 'Manual review',
        };
    }

    /** One-line explanation for the booking-requests approval page. */
    public function deskBookingPolicyAdminBannerText(): string
    {
        return match ($this->deskBookingPolicyNormalized()) {
            self::DESK_BOOKING_POLICY_AUTO_APPROVE => 'Desk submissions are confirmed automatically. Nothing is queued here—change handling under Settings if you want to approve each request yourself.',
            self::DESK_BOOKING_POLICY_AUTO_DENY => 'Desk submissions are declined automatically. Nothing is queued here—change handling under Settings if you want to review requests.',
            default => 'Desk staff submit manual booking requests from the desk portal. Approve to confirm the slot, or deny to release it and record a reason.',
        };
    }

    /** @return list<string> */
    public static function subscriptionTierValues(): array
    {
        return [
            self::TIER_BASIC,
            self::TIER_PREMIUM,
        ];
    }

    public function subscriptionTierNormalized(): string
    {
        $t = (string) ($this->subscription_tier ?? '');

        return in_array($t, self::subscriptionTierValues(), true)
            ? $t
            : self::TIER_BASIC;
    }

    public function hasPremiumSubscription(): bool
    {
        return $this->subscriptionTierNormalized() === self::TIER_PREMIUM;
    }
}
