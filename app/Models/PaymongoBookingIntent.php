<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymongoBookingIntent extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'court_client_id',
        'amount_centavos',
        'currency',
        'payload_json',
        'paymongo_checkout_session_id',
        'paymongo_payment_id',
        'status',
        'booking_request_id',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'amount_centavos' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courtClient(): BelongsTo
    {
        return $this->belongsTo(CourtClient::class);
    }
}
