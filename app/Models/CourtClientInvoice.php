<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class CourtClientInvoice extends Model
{
    use HasUuids;

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PAID = 'paid';

    protected $table = 'court_client_invoices';

    protected $fillable = [
        'court_client_id',
        'period_from',
        'period_to',
        'reference',
        'status',
        'paid_at',
        'total_cents',
        'currency',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'paid_at' => 'datetime',
            'total_cents' => 'integer',
        ];
    }

    public function courtClient(): BelongsTo
    {
        return $this->belongsTo(CourtClient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'invoice_bookings', 'court_client_invoice_id', 'booking_id')
            ->withPivot('amount_cents')
            ->withTimestamps();
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public static function generateReference(): string
    {
        return 'INV-'.now()->format('Ymd').'-'.strtoupper(substr((string) Str::uuid(), 0, 8));
    }
}
