<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingFeeSetting extends Model
{
    public const DEFAULT_BASE_FEE = '15.00';

    public const DEFAULT_PERCENTAGE_FEE = '0.0200';

    public const DEFAULT_MAX_FEE = '60.00';

    protected $fillable = [
        'base_fee',
        'percentage_fee',
        'max_fee',
        'is_active',
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
     * Human-readable fee rule for checkout (e.g. "₱15 base + 2% service fee").
     */
    public function breakdownLabel(): string
    {
        $base = (float) $this->base_fee;
        $baseLabel = fmod($base, 1.0) === 0.0
            ? (string) (int) $base
            : number_format($base, 2, '.', '');

        $pct = (float) $this->percentage_fee * 100;
        $pctLabel = fmod($pct, 1.0) === 0.0
            ? (string) (int) $pct
            : rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.');

        return '₱'.$baseLabel.' base + '.$pctLabel.'% service fee';
    }
}
