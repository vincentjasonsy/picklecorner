<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'hourly_rate_cents' => 'integer',
            'peak_hourly_rate_cents' => 'integer',
            'is_available' => 'boolean',
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
            ? ['indoor-a.svg', 'indoor-b.svg']
            : ['outdoor-a.svg', 'outdoor-b.svg'];
        $i = abs(crc32((string) $this->id)) % count($files);

        return asset('images/courts/'.$files[$i]);
    }

    /**
     * Schedule / manual-booking grids: outdoor courts first, then indoor; within each type by name (A–Z), then sort_order.
     *
     * @param  Collection<int, self>|\Illuminate\Database\Eloquent\Collection<int, self>  $courts
     * @return Collection<int, self>
     */
    public static function orderedForGridColumns(Collection $courts): Collection
    {
        return $courts->sortBy([
            fn (self $c) => $c->environment === self::ENV_INDOOR ? 1 : 0,
            fn (self $c) => Str::lower($c->name),
            fn (self $c) => $c->sort_order,
        ])->values();
    }
}
