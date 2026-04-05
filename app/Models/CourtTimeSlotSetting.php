<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtTimeSlotSetting extends Model
{
    use HasUuids;

    public const MODE_NORMAL = 'normal';

    public const MODE_PEAK = 'peak';

    public const MODE_MANUAL = 'manual';

    protected $table = 'court_time_slot_settings';

    protected $fillable = [
        'court_id',
        'day_of_week',
        'slot_start_hour',
        'mode',
        'amount_cents',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'slot_start_hour' => 'integer',
            'amount_cents' => 'integer',
        ];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
