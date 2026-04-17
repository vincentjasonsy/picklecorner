<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Court extends Model
{
    use HasUuids;

    public const ENV_INDOOR = 'indoor';

    public const ENV_OUTDOOR = 'outdoor';

    /** 1-based index within the same environment (outdoor vs indoor). */
    public static function defaultName(string $environment, int $ordinalWithinType): string
    {
        return match ($environment) {
            self::ENV_INDOOR => 'Indoor '.$ordinalWithinType,
            default => 'Outdoor '.$ordinalWithinType,
        };
    }

    protected $fillable = [
        'court_client_id',
        'name',
        'sort_order',
        'environment',
        'hourly_rate_cents',
        'peak_hourly_rate_cents',
        'is_available',
        'opens_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'hourly_rate_cents' => 'integer',
            'peak_hourly_rate_cents' => 'integer',
            'is_available' => 'boolean',
            'opens_at' => 'datetime',
            'opening_notice_sent_at' => 'datetime',
        ];
    }

    public function courtClient(): BelongsTo
    {
        return $this->belongsTo(CourtClient::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function timeSlotSettings(): HasMany
    {
        return $this->hasMany(CourtTimeSlotSetting::class);
    }

    public function timeSlotBlocks(): HasMany
    {
        return $this->hasMany(CourtTimeSlotBlock::class);
    }

    public function dateSlotBlocks(): HasMany
    {
        return $this->hasMany(CourtDateSlotBlock::class);
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(CourtGalleryImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function approvedGalleryImages(): HasMany
    {
        return $this->galleryImages()->whereNotNull('approved_at');
    }

    public function isWeeklySlotBlocked(int $dayOfWeek, int $slotStartHour): bool
    {
        if ($this->relationLoaded('timeSlotBlocks')) {
            return $this->timeSlotBlocks->contains(
                fn (CourtTimeSlotBlock $b) => $b->day_of_week === $dayOfWeek && $b->slot_start_hour === $slotStartHour,
            );
        }

        return $this->timeSlotBlocks()
            ->where('day_of_week', $dayOfWeek)
            ->where('slot_start_hour', $slotStartHour)
            ->exists();
    }

    public function effectiveHourlyRateCents(): ?int
    {
        return $this->hourly_rate_cents ?? $this->courtClient?->hourly_rate_cents;
    }

    public function effectivePeakHourlyRateCents(): ?int
    {
        return $this->peak_hourly_rate_cents ?? $this->courtClient?->peak_hourly_rate_cents;
    }

    /**
     * Static placeholder art per court (outdoor vs indoor + stable variety from id).
     */
    public function staticImageUrl(): string
    {
        $files = $this->environment === self::ENV_INDOOR
            ? ['indoor-a.jpg', 'indoor-b.jpg']
            : ['outdoor-a.jpg', 'outdoor-b.jpg'];
        $i = abs(crc32((string) $this->id)) % count($files);

        return asset('images/courts/'.$files[$i]);
    }

    /** First uploaded gallery image, or {@see staticImageUrl()}. */
    public function primaryDisplayImageUrl(): string
    {
        if ($this->relationLoaded('approvedGalleryImages')) {
            $first = $this->approvedGalleryImages->first();
        } else {
            $first = $this->approvedGalleryImages()->first();
        }

        return $first !== null ? $first->publicUrl() : $this->staticImageUrl();
    }

    /**
     * Slides for carousels (browse, public court page). Falls back to one static image when no uploads.
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
            $slides[] = ['src' => $this->staticImageUrl(), 'alt' => $this->name];
        }

        return $slides;
    }

    /**
     * Schedule / manual-booking grids: outdoor courts first (ascending by trailing number in the name), then indoor
     * (descending by that number). When no number is found in the name, {@see $sort_order} is used for that comparison;
     * ties break on name, then sort_order.
     *
     * @param  Collection<int, self>|\Illuminate\Database\Eloquent\Collection<int, self>  $courts
     * @return Collection<int, self>
     */
    public static function orderedForGridColumns(Collection $courts): Collection
    {
        return $courts->sort(function (self $a, self $b): int {
            $aOutdoor = $a->environment !== self::ENV_INDOOR;
            $bOutdoor = $b->environment !== self::ENV_INDOOR;
            if ($aOutdoor !== $bOutdoor) {
                return $aOutdoor ? -1 : 1;
            }

            $na = self::numericSuffixFromName($a->name);
            $nb = self::numericSuffixFromName($b->name);
            $ka = $na ?? $a->sort_order;
            $kb = $nb ?? $b->sort_order;

            if ($aOutdoor) {
                $cmp = $ka <=> $kb;
            } else {
                $cmp = $kb <=> $ka;
            }

            if ($cmp !== 0) {
                return $cmp;
            }

            $nameCmp = strcasecmp($a->name, $b->name);

            return $nameCmp !== 0 ? $nameCmp : ($a->sort_order <=> $b->sort_order);
        })->values();
    }

    private static function numericSuffixFromName(string $name): ?int
    {
        if (preg_match('/(\d+)\s*$/u', trim($name), $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
