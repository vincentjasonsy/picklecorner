<?php

namespace App\Support;

/**
 * Converts between user-entered Philippine peso amounts and integer minor units stored in the database.
 */
final class PesosMoneyForm
{
    public static function centsToPesoField(?int $cents): string
    {
        if ($cents === null) {
            return '';
        }

        $pesos = $cents / 100;

        return fmod($pesos, 1.0) === 0.0
            ? (string) (int) $pesos
            : number_format($pesos, 2, '.', '');
    }

    public static function pesoFieldToCents(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return (int) round((float) $value * 100);
    }

    /** Validation regex: empty or positive pesos with up to 2 decimal places. */
    public static function pesoFieldRegex(): string
    {
        return '^$|^\d+(\.\d{1,2})?$';
    }
}
