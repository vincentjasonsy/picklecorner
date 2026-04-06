<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachHourAvailability extends Model
{
    use HasUuids;

    protected $fillable = [
        'coach_user_id',
        'court_id',
        'date',
        'hour',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hour' => 'integer',
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_user_id');
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
