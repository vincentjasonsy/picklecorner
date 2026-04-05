<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtTimeSlotBlock extends Model
{
    use HasUuids;

    protected $table = 'court_time_slot_blocks';

    protected $fillable = [
        'court_id',
        'day_of_week',
        'slot_start_hour',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'slot_start_hour' => 'integer',
        ];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
