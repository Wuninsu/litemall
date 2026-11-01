<?php

namespace App\Support;

/**
 * Ghana phone numbers. Stored in E.164 form (+233XXXXXXXXX) so the same number typed in different
 * ways ("024 412 3456", "0244123456", "+233 24 412 3456") is recognised as one customer.
 */
final class PhoneNumber
{
    public const COUNTRY_CODE = '233';

    /**
     * Returns "+233XXXXXXXXX", or null when the text is not a Ghana number.
     *
     * Accepted: 0XXXXXXXXX, 233XXXXXXXXX, +233XXXXXXXXX, 00233XXXXXXXXX, with spaces, dashes,
     * dots or brackets. The 9-digit national number must start with 2, 3 or 5 (mobile and fixed-line ranges).
     */
    public static function normalise(string $input): ?string
    {
        $digits = preg_replace('/[\s\-().]/', '', trim($input));

        $national = match (true) {
            (bool) preg_match('/^\+233(\d{9})$/', $digits, $match) => $match[1],
            (bool) preg_match('/^00233(\d{9})$/', $digits, $match) => $match[1],
            (bool) preg_match('/^233(\d{9})$/', $digits, $match) => $match[1],
            (bool) preg_match('/^0(\d{9})$/', $digits, $match) => $match[1],
            default => null,
        };

        if ($national === null || ! preg_match('/^[235]/', $national)) {
            return null;
        }

        return '+'.self::COUNTRY_CODE.$national;
    }

    /**
     * "+233244123456" → "024 412 3456". Anything not in stored form is returned unchanged.
     */
    public static function format(string $e164): string
    {
        if (! preg_match('/^\+233(\d{2})(\d{3})(\d{4})$/', $e164, $parts)) {
            return $e164;
        }

        return "0{$parts[1]} {$parts[2]} {$parts[3]}";
    }

    /**
     * Turns what someone typed into a search box into a fragment that matches stored numbers:
     * "0244 12" → "24412", "+233 24" → "24". Returns null when there are fewer than 3 digits.
     */
    public static function searchFragment(string $input): ?string
    {
        $digits = preg_replace('/\D/', '', $input);

        $digits = match (true) {
            str_starts_with($digits, '00233') => substr($digits, 5),
            str_starts_with($digits, '233') && strlen($digits) > 3 => substr($digits, 3),
            str_starts_with($digits, '0') => substr($digits, 1),
            default => $digits,
        };

        return strlen($digits) >= 3 ? $digits : null;
    }
}
