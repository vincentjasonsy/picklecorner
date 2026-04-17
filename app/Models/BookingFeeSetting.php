<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingFeeSetting extends Model
{
    public const FEE_BASIS_SUBTOTAL = 'subtotal';

    public const FEE_BASIS_PER_COURT_HOUR = 'per_court_hour';

    public const PER_COURT_HOUR_FIXED = 'fixed';

    public const PER_COURT_HOUR_PERCENT = 'percent';

    public const DEFAULT_BASE_FEE = '15.00';

    public const DEFAULT_PERCENTAGE_FEE = '0.0200';

    public const DEFAULT_MAX_FEE = '60.00';

    public const DEFAULT_PER_COURT_HOUR_FIXED = '0.00';

    public const DEFAULT_PER_COURT_HOUR_PERCENT = '0.0200';

    protected $fillable = [
        'base_fee',
        'percentage_fee',
        'max_fee',
        'is_active',
        'fee_basis',
        'per_court_hour_mode',
        'per_court_hour_fixed',
        'per_court_hour_percent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_fee' => 'decimal:2',
            'percentage_fee' => 'decimal:4',
            'max_fee' => 'decimal:2',
            'is_active' => 'boolean',
            'per_court_hour_fixed' => 'decimal:2',
            'per_court_hour_percent' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (BookingFeeSetting $setting): void {
            if ($setting->is_active) {
                static::query()
                    ->whereKeyNot($setting->getKey())
                    ->update(['is_active' => false]);
            }
        });
    }

    /**
     * Human-readable fee rule for checkout (e.g. "₱15 base + 2% of courts subtotal").
     */
    public function breakdownLabel(): string
    {
        $basis = $this->fee_basis ?: self::FEE_BASIS_SUBTOTAL;

        if ($basis === self::FEE_BASIS_PER_COURT_HOUR) {
            $mode = $this->per_court_hour_mode ?: self::PER_COURT_HOUR_FIXED;

            if ($mode === self::PER_COURT_HOUR_PERCENT) {
                $pct = (float) ($this->per_court_hour_percent ?? self::DEFAULT_PER_COURT_HOUR_PERCENT) * 100;
                $pctLabel = fmod($pct, 1.0) === 0.0
                    ? (string) (int) $pct
                    : rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.');

                return $pctLabel.'% of each court hour rental (per hour)';
            }

            $fixed = (float) ($this->per_court_hour_fixed ?? self::DEFAULT_PER_COURT_HOUR_FIXED);
            $fixedLabel = fmod($fixed, 1.0) === 0.0
                ? (string) (int) $fixed
                : number_format($fixed, 2, '.', '');

            return '₱'.$fixedLabel.' per court hour (fixed)';
        }

        $base = (float) $this->base_fee;
        $baseLabel = fmod($base, 1.0) === 0.0
            ? (string) (int) $base
            : number_format($base, 2, '.', '');

        $pct = (float) $this->percentage_fee * 100;
        $pctLabel = fmod($pct, 1.0) === 0.0
            ? (string) (int) $pct
            : rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.');

        return '₱'.$baseLabel.' base + '.$pctLabel.'% of courts subtotal';
    }
}
