<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtDateSlotBlock extends Model
{
    use HasUuids;

    protected $table = 'court_date_slot_blocks';

    protected $fillable = [
        'court_id',
        'blocked_date',
        'slot_start_hour',
    ];

    protected function casts(): array
    {
        return [
            'blocked_date' => 'date',
            'slot_start_hour' => 'integer',
        ];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
