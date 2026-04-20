<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class CityFeaturedCourtClient extends Model
{
    use HasUuids;

    protected $fillable = [
        'city',
        'court_client_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function courtClient(): BelongsTo
    {
        return $this->belongsTo(CourtClient::class);
    }

    /**
     * @return Collection<int, CourtClient>
     */
    public static function activeVenuesForCityOrdered(string $city): Collection
    {
        return static::query()
            ->where('city', $city)
            ->whereHas('courtClient', fn ($q) => $q->wherePubliclyBookable())
            ->orderBy('sort_order')
            ->with(['courtClient' => fn ($q) => $q->with('approvedGalleryImages')])
            ->get()
            ->map(fn (self $row) => $row->courtClient)
            ->filter()
            ->values();
    }
}
