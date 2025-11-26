<?php

namespace ElliePHP\Components\Support\Util;

final class Str
{
    public static function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    public static function startsWithAny(string $haystack, array $needles): bool
    {
        return array_any($needles, static fn($needle): bool => self::startsWith($haystack, $needle));
    }

    public static function containsAny(string $haystack, array $needles): bool
    {
        return array_any($needles, static fn($needle): bool => self::contains($haystack, $needle));
    }

    public static function endsWithAny(string $haystack, array $needles): bool
    {
        return array_any($needles, static fn($needle): bool => self::endsWith($haystack, $needle));
    }

    public static function endsWith(string $haystack, string $needle): bool
    {
        return $needle === "" || str_ends_with($haystack, $needle);
    }

    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    public static function containsAll(string $haystack, array $needles): bool
    {
        return array_all($needles, static fn($needle): bool => self::contains($haystack, $needle));
    }

    public static function toCamelCase(string $string): string
    {
        return lcfirst(
            str_replace(
                " ",
                "",
                ucwords(str_replace(["-", "_"], " ", $string)),
            ),
        );
    }

    public static function toPascalCase(string $string): string
    {
        return ucfirst(self::toCamelCase($string));
    }

    public static function toSnakeCase(string $string): string
    {
        return strtolower(
            preg_replace(
                "/([a-z])([A-Z])/",
                '$1_$2',
                str_replace(" ", "_", $string),
            ),
        );
    }

    public static function toKebabCase(string $string): string
    {
        return strtolower(
            preg_replace(
                "/([a-z])([A-Z])/",
                '$1-$2',
                str_replace(" ", "-", $string),
            ),
        );
    }

    /**
     * Limits the length of a string to the specified number of characters.
     *
     * If the string exceeds the specified limit, it is truncated and the specified
     * ending string is appended to the result.
     *
     * @param string $string The input string to limit.
     * @param int $limit The maximum length of the string. Default is 100.
     * @param string $end The string to append if the input string is truncated. Default is "...".
     *
     * @return string The limited string with the specified ending if truncated.
     */
    public static function limit(
        string $string,
        int    $limit = 100,
        string $end = "...",
    ): string
    {
        return strlen($string) <= $limit
            ? $string
            : substr($string, 0, $limit) . $end;
    }

    public static function truncateWords(
        string $string,
        int    $words = 10,
        string $end = "...",
    ): string
    {
        $parts = preg_split("/\s+/", $string);
        return count($parts) <= $words
            ? $string
            : implode(" ", array_slice($parts, 0, $words)) . $end;
    }

    public static function words(string $string, int $words = 10): string
    {
        $parts = preg_split("/\s+/", trim($string));
        return implode(" ", array_slice($parts, 0, $words));
    }

    public static function wordCount(string $string): int
    {
        return count(preg_split("/\s+/", trim($string), -1, PREG_SPLIT_NO_EMPTY));
    }

    public static function clean(string $string): string
    {
        $result = preg_replace("/[^\\p{L}\\p{N}\\s]/u", "", $string);
        return $result === null ? "" : trim($result);
    }

    public static function replace(
        string $search,
        string $replace,
        string $subject,
    ): string
    {
        return str_replace($search, $replace, $subject);
    }

    public static function replaceFirst(
        string $search,
        string $replace,
        string $subject,
    ): string
    {
        $pos = strpos($subject, $search);
        if ($pos === false) {
            return $subject;
        }
        return substr_replace($subject, $replace, $pos, strlen($search));
    }

    public static function replaceLast(
        string $search,
        string $replace,
        string $subject,
    ): string
    {
        $pos = strrpos($subject, $search);
        if ($pos === false) {
            return $subject;
        }
        return substr_replace($subject, $replace, $pos, strlen($search));
    }

    public static function replaceArray(
        array  $search,
        array  $replace,
        string $subject,
    ): string
    {
        return str_replace($search, $replace, $subject);
    }

    public static function toUpperCase(string $string): string
    {
        return strtoupper($string);
    }

    public static function toLowerCase(
        ?string $string,
        ?string $encoding = "UTF-8",
    ): string
    {
        return mb_strtolower((string)$string, $encoding);
    }

    public static function title(string $string): string
    {
        return ucwords(self::toLowerCase($string));
    }

    public static function ucfirst(string $string): string
    {
        return ucfirst($string);
    }

    public static function lcfirst(string $string): string
    {
        return lcfirst($string);
    }

    public static function reverse(string $string): string
    {
        return strrev($string);
    }

    public static function slug(string $string, string $separator = "-"): string
    {
        $string = strtolower(
            trim(
                (string)preg_replace("/[^A-Za-z0-9-]+/", $separator, $string),
            ),
        );
        return trim($string, $separator);
    }

    public static function length(string $string): int
    {
        return mb_strlen($string);
    }

    public static function isEmpty(string $string): bool
    {
        return trim($string) === "";
    }

    public static function isNotEmpty(string $string): bool
    {
        return !self::isEmpty($string);
    }

    public static function padLeft(
        string $string,
        int    $length,
        string $pad = " ",
    ): string
    {
        return str_pad($string, $length, $pad, STR_PAD_LEFT);
    }

    public static function padRight(
        string $string,
        int    $length,
        string $pad = " ",
    ): string
    {
        return str_pad($string, $length, $pad);
    }

    public static function padBoth(
        string $string,
        int    $length,
        string $pad = " ",
    ): string
    {
        return str_pad($string, $length, $pad, STR_PAD_BOTH);
    }

    /**
     * Executes a regular expression match on a given string.
     *
     * If the pattern matches the subject, the function returns an array with the matches.
     * If the pattern does not match the subject, the function returns null.
     *
     * @param string $pattern The regular expression pattern to match.
     * @param string $subject The string to search for the pattern in.
     *
     * @return array|null The matches if the pattern matches, null otherwise.
     */
    public static function match(string $pattern, string $subject): ?array
    {
        return preg_match($pattern, $subject, $matches) ? $matches : null;
    }

    /**
     * Executes a global regular expression match on a given string.
     *
     * Returns all matches as an array of arrays, or null if no matches found.
     *
     * @param string $pattern The regular expression pattern to match.
     * @param string $subject The string to search for the pattern in.
     *
     * @return array|null All matches if the pattern matches, null otherwise.
     */
    public static function matchAll(string $pattern, string $subject): ?array
    {
        return preg_match_all($pattern, $subject, $matches) ? $matches : null;
    }

    /**
     * Generates a random string of the given length.
     *
     * The string will contain a mix of uppercase and lowercase letters, as well as numbers.
     *
     * @param int $length The length of the string to generate. Defaults to 16.
     *
     * @return string The generated random string.
     */
    public static function random(int $length = 16): string
    {
        return substr(
            str_shuffle(
                str_repeat(
                    "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ",
                    (int)ceil($length / 62),
                ),
            ),
            0,
            $length,
        );
    }

    /**
     * Extracts a substring between two given strings from a given string.
     *
     * If the start or end strings are not found, the function returns null.
     *
     * @param string $string The string to extract from.
     * @param string $start The starting string.
     * @param string $end The ending string.
     *
     * @return string|null The extracted substring or null if the start or end strings are not found.
     */
    public static function extractStringBetween(
        string $string,
        string $start,
        string $end,
    ): ?string
    {
        $startPos = strpos($string, $start);
        if ($startPos === false) {
            return null;
        }
        $endPos = strpos($string, $end, $startPos + strlen($start));
        if ($endPos === false) {
            return null;
        }
        return mb_substr(
            $string,
            $startPos + strlen($start),
            $endPos - $startPos - strlen($start),
        );
    }

    /**
     * Get a substring from the string.
     *
     * @param string $string The input string.
     * @param int $start The starting position.
     * @param int|null $length The length of the substring.
     *
     * @return string The extracted substring.
     */
    public static function substr(string $string, int $start, ?int $length = null): string
    {
        return mb_substr($string, $start, $length);
    }

    /**
     * Get the portion of a string before a given value.
     *
     * @param string $string The input string.
     * @param string $search The search value.
     *
     * @return string The portion before the search value, or the entire string if not found.
     */
    public static function before(string $string, string $search): string
    {
        $pos = strpos($string, $search);
        return $pos === false ? $string : substr($string, 0, $pos);
    }

    /**
     * Get the portion of a string after a given value.
     *
     * @param string $string The input string.
     * @param string $search The search value.
     *
     * @return string The portion after the search value, or an empty string if not found.
     */
    public static function after(string $string, string $search): string
    {
        $pos = strpos($string, $search);
        return $pos === false ? "" : substr($string, $pos + strlen($search));
    }

    /**
     * Get the portion of a string before the last occurrence of a given value.
     *
     * @param string $string The input string.
     * @param string $search The search value.
     *
     * @return string The portion before the last occurrence, or the entire string if not found.
     */
    public static function beforeLast(string $string, string $search): string
    {
        $pos = strrpos($string, $search);
        return $pos === false ? $string : substr($string, 0, $pos);
    }

    /**
     * Get the portion of a string after the last occurrence of a given value.
     *
     * @param string $string The input string.
     * @param string $search The search value.
     *
     * @return string The portion after the last occurrence, or an empty string if not found.
     */
    public static function afterLast(string $string, string $search): string
    {
        $pos = strrpos($string, $search);
        return $pos === false ? "" : substr($string, $pos + strlen($search));
    }

    /**
     * Repeat a string a given number of times.
     *
     * @param string $string The string to repeat.
     * @param int $times The number of times to repeat.
     *
     * @return string The repeated string.
     */
    public static function repeat(string $string, int $times): string
    {
        return str_repeat($string, $times);
    }

    /**
     * Remove whitespace from the beginning and end of a string.
     *
     * @param string $string The input string.
     * @param string $characters Optional characters to trim.
     *
     * @return string The trimmed string.
     */
    public static function trim(string $string, string $characters = " \t\n\r\0\x0B"): string
    {
        return trim($string, $characters);
    }

    /**
     * Remove whitespace from the beginning of a string.
     *
     * @param string $string The input string.
     * @param string $characters Optional characters to trim.
     *
     * @return string The trimmed string.
     */
    public static function ltrim(string $string, string $characters = " \t\n\r\0\x0B"): string
    {
        return ltrim($string, $characters);
    }

    /**
     * Remove whitespace from the end of a string.
     *
     * @param string $string The input string.
     * @param string $characters Optional characters to trim.
     *
     * @return string The trimmed string.
     */
    public static function rtrim(string $string, string $characters = " \t\n\r\0\x0B"): string
    {
        return rtrim($string, $characters);
    }

    /**
     * Remove the given substring from the start of the string.
     *
     * @param string $string The input string.
     * @param string $prefix The prefix to remove.
     *
     * @return string The string without the prefix.
     */
    public static function removePrefix(string $string, string $prefix): string
    {
        return self::startsWith($string, $prefix)
            ? substr($string, strlen($prefix))
            : $string;
    }

    /**
     * Remove the given substring from the end of the string.
     *
     * @param string $string The input string.
     * @param string $suffix The suffix to remove.
     *
     * @return string The string without the suffix.
     */
    public static function removeSuffix(string $string, string $suffix): string
    {
        return self::endsWith($string, $suffix)
            ? substr($string, 0, -strlen($suffix))
            : $string;
    }

    /**
     * Ensure a string starts with a given prefix.
     *
     * @param string $string The input string.
     * @param string $prefix The prefix to ensure.
     *
     * @return string The string with the prefix.
     */
    public static function ensurePrefix(string $string, string $prefix): string
    {
        return self::startsWith($string, $prefix) ? $string : $prefix . $string;
    }

    /**
     * Ensure a string ends with a given suffix.
     *
     * @param string $string The input string.
     * @param string $suffix The suffix to ensure.
     *
     * @return string The string with the suffix.
     */
    public static function ensureSuffix(string $string, string $suffix): string
    {
        return self::endsWith($string, $suffix) ? $string : $string . $suffix;
    }

    /**
     * Convert a string to an array of characters.
     *
     * @param string $string The input string.
     *
     * @return array<string> The array of characters.
     */
    public static function toArray(string $string): array
    {
        return preg_split("//u", $string, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Check if a string is valid JSON.
     *
     * @param string $string The string to check.
     *
     * @return bool True if valid JSON, false otherwise.
     */
    public static function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check if a string is a valid URL.
     *
     * @param string $string The string to check.
     *
     * @return bool True if valid URL, false otherwise.
     */
    public static function isUrl(string $string): bool
    {
        return filter_var($string, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if a string is a valid email address.
     *
     * @param string $string The string to check.
     *
     * @return bool True if valid email, false otherwise.
     */
    public static function isEmail(string $string): bool
    {
        return filter_var($string, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if a string contains only alphanumeric characters.
     *
     * @param string $string The string to check.
     *
     * @return bool True if alphanumeric, false otherwise.
     */
    public static function isAlphanumeric(string $string): bool
    {
        return ctype_alnum($string);
    }

    /**
     * Check if a string contains only alphabetic characters.
     *
     * @param string $string The string to check.
     *
     * @return bool True if alphabetic, false otherwise.
     */
    public static function isAlpha(string $string): bool
    {
        return ctype_alpha($string);
    }

    /**
     * Check if a string contains only numeric characters.
     *
     * @param string $string The string to check.
     *
     * @return bool True if numeric, false otherwise.
     */
    public static function isNumeric(string $string): bool
    {
        return is_numeric($string);
    }

    /**
     * Mask a portion of a string with a repeated character.
     *
     * @param string $string The input string.
     * @param string $character The masking character.
     * @param int $index The starting index (negative for from end).
     * @param int|null $length The length to mask (null for remaining).
     *
     * @return string The masked string.
     */
    public static function mask(
        string $string,
        string $character = "*",
        int    $index = 0,
        ?int   $length = null
    ): string
    {
        if ($index === 0 && $length === null) {
            return str_repeat($character, mb_strlen($string));
        }

        $segment = mb_substr($string, $index, $length);
        $strlen = mb_strlen($string);
        $startIdx = $index < 0 ? $index + $strlen : $index;
        $start = mb_substr($string, 0, $startIdx);
        $segmentLen = mb_strlen($segment);
        $end = mb_substr($string, $startIdx + $segmentLen);

        return $start . str_repeat($character, $segmentLen) . $end;
    }

    /**
     * Swap keywords in a string with values from an array.
     *
     * @param string $string The template string with placeholders.
     * @param array $replacements Associative array of replacements.
     *
     * @return array|string The string with replacements applied.
     */
    public static function swap(string $string, array $replacements): array|string
    {
        return strtr($string, $replacements);
    }

    /**
     * Split a string by a separator.
     *
     * @param string $seperator The delimiter to split by.
     * @param string $string The input string.
     * @param int $limit Max number of elements to return.
     *
     * @return array<string> The exploded parts.
     */
    public static function split(
        string $seperator,
        string $string,
        int    $limit = PHP_INT_MAX
    ): array
    {
        return explode($seperator, $string, $limit);
    }

    /**
     * Ensure a value is a valid UTF-8 string.
     * Returns null if the value isn't a string.
     *
     * @param mixed $value Any input value.
     *
     * @return string|null UTF-8 cleaned string or null.
     */
    public static function cleanUtf8(
        mixed $value
    ): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        return mb_convert_encoding($value, "UTF-8", "UTF-8");
    }

    /**
     * Convert a string to plural form (basic English rules).
     *
     * @param string $string The singular word.
     * @param int $count The count to determine plurality.
     *
     * @return string The plural form if count != 1, otherwise singular.
     */
    public static function plural(string $string, int $count = 2): string
    {
        if ($count === 1) {
            return $string;
        }

        $rules = [
            '/(quiz)$/i' => "$1zes",
            '/^(ox)$/i' => "$1en",
            '/([m|l])ouse$/i' => "$1ice",
            '/(matr|vert|ind)ix|ex$/i' => "$1ices",
            '/(x|ch|ss|sh)$/i' => "$1es",
            '/([^aeiouy]|qu)y$/i' => "$1ies",
            '/(hive)$/i' => "$1s",
            '/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
            '/(shea|lea|loa|thie)f$/i' => "$1ves",
            '/sis$/i' => "ses",
            '/([ti])um$/i' => "$1a",
            '/(tomat|potat|ech|her|vet)o$/i' => "$1oes",
            '/(bu)s$/i' => "$1ses",
            '/(alias)$/i' => "$1es",
            '/(octop)us$/i' => "$1i",
            '/(ax|test)is$/i' => "$1es",
            '/(us)$/i' => "$1es",
            '/s$/i' => "s",
            '/$/' => "s"
        ];

        foreach ($rules as $rule => $replacement) {
            if (preg_match($rule, $string)) {
                return preg_replace($rule, $replacement, $string);
            }
        }

        return $string;
    }

    /**
     * Convert a string to singular form (basic English rules).
     *
     * @param string $string The plural word.
     *
     * @return string The singular form.
     */
    public static function singular(string $string): string
    {
        $rules = [
            '/(quiz)zes$/i' => "$1",
            '/(matr)ices$/i' => "$1ix",
            '/(vert|ind)ices$/i' => "$1ex",
            '/^(ox)en$/i' => "$1",
            '/(alias)es$/i' => "$1",
            '/(octop|vir)i$/i' => "$1us",
            '/(cris|ax|test)es$/i' => "$1is",
            '/(shoe)s$/i' => "$1",
            '/(o)es$/i' => "$1",
            '/(bus)es$/i' => "$1",
            '/([m|l])ice$/i' => "$1ouse",
            '/(x|ch|ss|sh)es$/i' => "$1",
            '/(m)ovies$/i' => "$1ovie",
            '/(s)eries$/i' => "$1eries",
            '/([^aeiouy]|qu)ies$/i' => "$1y",
            '/([lr])ves$/i' => "$1f",
            '/(tive)s$/i' => "$1",
            '/(hive)s$/i' => "$1",
            '/(li|wi|kni)ves$/i' => "$1fe",
            '/(shea|loa|lea|thie)ves$/i' => "$1f",
            '/(^analy)ses$/i' => "$1sis",
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => "$1$2sis",
            '/([ti])a$/i' => "$1um",
            '/(n)ews$/i' => "$1ews",
            '/(h|bl)ouses$/i' => "$1ouse",
            '/(corpse)s$/i' => "$1",
            '/(us)es$/i' => "$1",
            '/s$/i' => ""
        ];

        foreach ($rules as $rule => $replacement) {
            if (preg_match($rule, $string)) {
                return preg_replace($rule, $replacement, $string);
            }
        }

        return $string;
    }
}