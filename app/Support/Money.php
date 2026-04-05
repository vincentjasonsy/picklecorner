<?php

namespace App\Support;

use Illuminate\Support\Number;

final class Money
{
    /**
     * @param  int|null  $minorUnits  Stored amount in smallest currency units (PHP: centavos). Display is full pesos via formatting.
     */
    public static function formatMinor(?int $minorUnits, ?string $currencyCode = null): string
    {
        if ($minorUnits === null) {
            return '—';
        }

        $code = $currencyCode ?? config('app.default_currency', 'PHP');
        $locale = config('app.money_locale', 'en_PH');
        $major = $minorUnits / 100;

        $formatted = Number::currency($major, $code, $locale);

        return $formatted !== false ? $formatted : number_format($major, 2).' '.$code;
    }
}
