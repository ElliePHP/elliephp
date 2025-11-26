<?php

namespace ElliePHP\Components\Support\Util;

use Throwable;

final class Num
{
    /**
     * Clamp a number between a minimum and maximum value.
     *
     * @param int|float $value The value to clamp.
     * @param int|float $min The minimum value.
     * @param int|float $max The maximum value.
     *
     * @return int|float The clamped value.
     */
    public static function clamp(int|float $value, int|float $min, int|float $max): int|float
    {
        return max($min, min($max, $value));
    }

    /**
     * Check if a number is within a range.
     *
     * @param int|float $value The value to check.
     * @param int|float $min The minimum value.
     * @param int|float $max The maximum value.
     * @param bool $inclusive Whether to include the boundaries.
     *
     * @return bool True if in range, false otherwise.
     */
    public static function inRange(int|float $value, int|float $min, int|float $max, bool $inclusive = true): bool
    {
        return $inclusive
            ? $value >= $min && $value <= $max
            : $value > $min && $value < $max;
    }

    /**
     * Calculate percentage of a value.
     *
     * @param int|float $value The value.
     * @param int|float $total The total.
     * @param int $decimals Number of decimal places.
     *
     * @return float The percentage.
     */
    public static function percentage(int|float $value, int|float $total, int $decimals = 2): float
    {
        if ($total === 0) {
            return 0.0;
        }
        return round(($value / $total) * 100, $decimals);
    }

    /**
     * Format a number with grouped thousands.
     *
     * @param int|float $number The number to format.
     * @param int $decimals Number of decimal places.
     * @param string $decimalSeparator Decimal separator.
     * @param string $thousandsSeparator Thousands separator.
     *
     * @return string The formatted number.
     */
    public static function format(
        int|float $number,
        int       $decimals = 0,
        string    $decimalSeparator = '.',
        string    $thousandsSeparator = ','
    ): string
    {
        return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Abbreviate a number (1000 → 1K, 1000000 → 1M).
     *
     * @param int|float $number The number to abbreviate.
     * @param int $decimals Number of decimal places.
     *
     * @return string The abbreviated number.
     */
    public static function abbreviate(int|float $number, int $decimals = 1): string
    {
        $suffixes = ['', 'K', 'M', 'B', 'T'];
        $tier = 0;

        if ($number < 1000) {
            return (string)$number;
        }

        while ($number >= 1000 && $tier < count($suffixes) - 1) {
            $number /= 1000;
            $tier++;
        }

        return round($number, $decimals) . $suffixes[$tier];
    }

    /**
     * Convert a number to its ordinal form (1 → 1st, 2 → 2nd).
     *
     * @param int $number The number.
     *
     * @return string The ordinal string.
     */
    public static function ordinal(int $number): string
    {
        $suffixes = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];

        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        }

        return $number . $suffixes[$number % 10];
    }

    /**
     * Convert a number to Roman numerals.
     *
     * @param int $number The number (1-3999).
     *
     * @return string The Roman numeral.
     */
    public static function toRoman(int $number): string
    {
        if ($number < 1 || $number > 3999) {
            return (string)$number;
        }

        $map = [
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
            'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
        ];

        $result = '';
        foreach ($map as $roman => $value) {
            while ($number >= $value) {
                $result .= $roman;
                $number -= $value;
            }
        }

        return $result;
    }

    /**
     * Convert Roman numerals to a number.
     *
     * @param string $roman The Roman numeral.
     *
     * @return int The number.
     */
    public static function fromRoman(string $roman): int
    {
        $map = [
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
            'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
        ];

        $result = 0;
        $roman = strtoupper($roman);

        foreach ($map as $key => $value) {
            while (str_starts_with($roman, $key)) {
                $result += $value;
                $roman = substr($roman, strlen($key));
            }
        }

        return $result;
    }

    /**
     * Round a number to the nearest multiple.
     *
     * @param int|float $number The number to round.
     * @param int|float $multiple The multiple.
     *
     * @return int|float The rounded number.
     */
    public static function roundToNearest(int|float $number, int|float $multiple): int|float
    {
        return round($number / $multiple) * $multiple;
    }

    /**
     * Check if a number is even.
     *
     * @param int $number The number.
     *
     * @return bool True if even, false otherwise.
     */
    public static function isEven(int $number): bool
    {
        return $number % 2 === 0;
    }

    /**
     * Check if a number is odd.
     *
     * @param int $number The number.
     *
     * @return bool True if odd, false otherwise.
     */
    public static function isOdd(int $number): bool
    {
        return $number % 2 !== 0;
    }

    /**
     * Get the absolute value of a number.
     *
     * @param int|float $number The number.
     *
     * @return int|float The absolute value.
     */
    public static function absolute(int|float $number): int|float
    {
        return abs($number);
    }

    /**
     * Calculate the average of an array of numbers.
     *
     * @param array $numbers The numbers.
     *
     * @return float The average.
     */
    public static function average(array $numbers): float
    {
        if (empty($numbers)) {
            return 0.0;
        }
        return array_sum($numbers) / count($numbers);
    }

    /**
     * Get the minimum value from an array of numbers.
     *
     * @param array $numbers The numbers.
     *
     * @return int|float|null The minimum value or null if empty.
     */
    public static function min(array $numbers): int|float|null
    {
        return empty($numbers) ? null : min($numbers);
    }

    /**
     * Get the maximum value from an array of numbers.
     *
     * @param array $numbers The numbers.
     *
     * @return int|float|null The maximum value or null if empty.
     */
    public static function max(array $numbers): int|float|null
    {
        return empty($numbers) ? null : max($numbers);
    }

    /**
     * Sum an array of numbers.
     *
     * @param array $numbers The numbers.
     *
     * @return int|float The sum.
     */
    public static function sum(array $numbers): int|float
    {
        return array_sum($numbers);
    }

    /**
     * Format bytes to human-readable size.
     *
     * @param int $bytes The number of bytes.
     * @param int $decimals Number of decimal places.
     *
     * @return string The formatted size.
     */
    public static function formatBytes(int $bytes, int $decimals = 2): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $factor = floor((strlen((string)$bytes) - 1) / 3);

        return sprintf("%.{$decimals}f %s", $bytes / (1024 ** $factor), $units[$factor]);
    }

    /**
     * Convert a value to an integer.
     *
     * @param mixed $value The value to convert.
     * @param int $default Default value if conversion fails.
     *
     * @return int The integer value.
     */
    public static function toInt(mixed $value, int $default = 0): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        return $default;
    }

    /**
     * Convert a value to a float.
     *
     * @param mixed $value The value to convert.
     * @param float $default Default value if conversion fails.
     *
     * @return float The float value.
     */
    public static function toFloat(mixed $value, float $default = 0.0): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        return $default;
    }


    public static function random(int $length = 16): int
    {
        $length = max(1, min($length, 18));

        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_pad('9', $length, '9');

        try {
            return random_int($min, $max);
        } catch (Throwable) {
            return $min;
        }
    }




}
