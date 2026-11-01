<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Converts between Ghana Cedi amounts typed or shown to people and integer pesewas stored in the
 * database (ADR-0005: 1 GHS = 100 pesewas). Uses string arithmetic only — never floats.
 */
final class Money
{
    public const SYMBOL = 'GH₵';

    /**
     * Largest amount accepted from input: GH₵ 9,999,999,999.99.
     */
    public const MAX_PESEWAS = 999_999_999_999;

    /**
     * Pattern for a cedi amount as typed: digits with optional thousands commas and up to two decimals.
     */
    public const INPUT_PATTERN = '/^\d{1,3}(,?\d{3})*(\.\d{1,2})?$|^\d+(\.\d{1,2})?$/';

    /**
     * "1,200.5" → 120050. Accepts an optional "GH₵"/"GHS" prefix and surrounding spaces.
     *
     * @throws InvalidArgumentException when the text is not a valid non-negative amount
     */
    public static function parse(string $amount): int
    {
        $normalised = trim(preg_replace('/^(GH₵|GHS|GH¢)\s*/iu', '', trim($amount)));

        if (! preg_match(self::INPUT_PATTERN, $normalised)) {
            throw new InvalidArgumentException("\"{$amount}\" is not a valid cedi amount.");
        }

        [$cedis, $fraction] = array_pad(explode('.', str_replace(',', '', $normalised), 2), 2, '');

        $cedis = ltrim($cedis, '0');

        if (strlen($cedis) > 10) {
            throw new InvalidArgumentException("\"{$amount}\" is too large.");
        }

        $pesewas = (int) (($cedis === '' ? '0' : $cedis).str_pad($fraction, 2, '0'));

        if ($pesewas > self::MAX_PESEWAS) {
            throw new InvalidArgumentException("\"{$amount}\" is too large.");
        }

        return $pesewas;
    }

    /**
     * Whether `parse()` would accept the text.
     */
    public static function isValid(string $amount): bool
    {
        try {
            self::parse($amount);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /**
     * 120050 → "GH₵ 1,200.50" (or "1,200.50" without the symbol). Negative amounts get a leading minus.
     */
    public static function format(int $pesewas, bool $withSymbol = true): string
    {
        $sign = $pesewas < 0 ? '-' : '';
        $absolute = abs($pesewas);

        $formatted = number_format(intdiv($absolute, 100)).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);

        return $sign.($withSymbol ? self::SYMBOL.' ' : '').$formatted;
    }

    /**
     * "12.5" → 1250 basis points (1 % = 100). Accepts 0–100 with up to two decimals and an optional "%".
     *
     * @throws InvalidArgumentException
     */
    public static function parsePercent(string $percent): int
    {
        $normalised = rtrim(trim($percent), "% \t");

        if (! preg_match('/^\d{1,3}(\.\d{1,2})?$/', $normalised)) {
            throw new InvalidArgumentException("\"{$percent}\" is not a valid percentage.");
        }

        [$whole, $fraction] = array_pad(explode('.', $normalised, 2), 2, '');
        $basisPoints = (int) ($whole.str_pad($fraction, 2, '0'));

        if ($basisPoints > 10_000) {
            throw new InvalidArgumentException("\"{$percent}\" is more than 100%.");
        }

        return $basisPoints;
    }

    /**
     * Percentage of an amount, rounded half up to the nearest pesewa:
     * percentOf(123450, 1000) = 10% of GH₵ 1,234.50 = 12345 (GH₵ 123.45).
     */
    public static function percentOf(int $pesewas, int $basisPoints): int
    {
        return intdiv($pesewas * $basisPoints + 5_000, 10_000);
    }

    /**
     * 120050 → "1200.50", the plain form used to pre-fill input fields.
     */
    public static function toInput(int $pesewas): string
    {
        return intdiv($pesewas, 100).'.'.str_pad((string) ($pesewas % 100), 2, '0', STR_PAD_LEFT);
    }
}
