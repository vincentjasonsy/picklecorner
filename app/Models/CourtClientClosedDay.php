<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtClientClosedDay extends Model
{
    use HasUuids;

    protected $table = 'court_client_closed_days';

    protected $fillable = [
        'court_client_id',
        'closed_on',
    ];

    protected function casts(): array
    {
        return [
            'closed_on' => 'date',
        ];
    }

    public function courtClient(): BelongsTo
    {
        return $this->belongsTo(CourtClient::class);
    }
}
