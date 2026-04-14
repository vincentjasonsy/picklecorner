<?php

namespace App\Services;

final class ProfanityChecker
{
    public static function containsProfanity(?string $text): bool
    {
        if ($text === null || trim($text) === '') {
            return false;
        }

        $normalized = mb_strtolower($text, 'UTF-8');
        $words = config('profanity.words', []);

        foreach ($words as $term) {
            $term = mb_strtolower(trim((string) $term), 'UTF-8');
            if ($term === '') {
                continue;
            }
            if (str_contains($term, ' ')) {
                if (str_contains($normalized, $term)) {
                    return true;
                }

                continue;
            }

            $quoted = preg_quote($term, '/');
            if (preg_match('/\b'.$quoted.'\b/iu', $text)) {
                return true;
            }
        }

        return false;
    }
}
