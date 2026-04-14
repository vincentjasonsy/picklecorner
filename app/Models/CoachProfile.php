<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'hourly_rate_cents',
        'currency',
        'bio',
        'public_rating_average',
        'public_rating_count',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate_cents' => 'integer',
            'public_rating_average' => 'decimal:1',
            'public_rating_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
