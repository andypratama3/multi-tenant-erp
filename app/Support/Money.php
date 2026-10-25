<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Decimal-safe money helpers built on bcmath.
 *
 * UI may preview totals, but persisted totals must always be calculated here
 * (or by a domain action) using decimal strings, never floats.
 */
final class Money
{
    public const SCALE = 2;

    public const COST_SCALE = 4;

    /**
     * @return numeric-string
     */
    public static function of(int|float|string $amount): string
    {
        if (is_float($amount)) {
            $amount = number_format($amount, self::COST_SCALE, '.', '');
        }

        $value = (string) $amount;

        if (! is_numeric($value)) {
            throw new InvalidArgumentException('Nilai uang tidak valid.');
        }

        return $value;
    }

    public static function add(int|float|string $left, int|float|string $right, int $scale = self::SCALE): string
    {
        return bcadd(self::of($left), self::of($right), $scale);
    }

    public static function sub(int|float|string $left, int|float|string $right, int $scale = self::SCALE): string
    {
        return bcsub(self::of($left), self::of($right), $scale);
    }

    public static function mul(int|float|string $left, int|float|string $right, int $scale = self::COST_SCALE): string
    {
        return bcmul(self::of($left), self::of($right), $scale);
    }

    public static function div(int|float|string $left, int|float|string $right, int $scale = self::COST_SCALE): string
    {
        if (bccomp(self::of($right), '0', self::COST_SCALE) === 0) {
            throw new InvalidArgumentException('Cannot divide money by zero.');
        }

        return bcdiv(self::of($left), self::of($right), $scale);
    }

    /**
     * Apply a percentage (0-100) to an amount and round to 2 decimals.
     */
    public static function percentage(int|float|string $amount, int|float|string $percent): string
    {
        return self::round(self::div(self::mul($amount, $percent, 6), '100', 6));
    }

    public static function round(int|float|string $amount, int $scale = self::SCALE): string
    {
        $factor = bcpow('10', (string) $scale, 0);
        $shifted = bcmul(self::of($amount), $factor, 8);
        $sign = bccomp($shifted, '0', 8) < 0 ? '-0.5' : '0.5';
        $rounded = bcadd($shifted, $sign, 0);

        return bcdiv($rounded, $factor, $scale);
    }

    /**
     * Normalize an arbitrary numeric value to a bcmath-safe decimal string.
     *
     * @return numeric-string
     */
    public static function decimal(int|float|string $value, int $scale = 3): string
    {
        return number_format((float) $value, $scale, '.', '');
    }

    public static function compare(int|float|string $left, int|float|string $right): int
    {
        return bccomp(self::of($left), self::of($right), self::COST_SCALE);
    }

    public static function isPositive(int|float|string $amount): bool
    {
        return self::compare($amount, '0') === 1;
    }

    public static function formatIdr(int|float|string $amount): string
    {
        return 'Rp '.number_format((float) self::round($amount), 0, ',', '.');
    }
}
