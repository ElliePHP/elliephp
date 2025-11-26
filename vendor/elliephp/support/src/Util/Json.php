<?php

namespace ElliePHP\Components\Support\Util;

use JsonException;
use SimpleXMLElement;

final class Json
{
    /**
     * Encode a value to JSON string.
     *
     * @param mixed $value The value to encode.
     * @param int $flags JSON encoding flags.
     * @param int $depth Maximum depth.
     *
     * @return string The JSON string.
     */
    public static function encode(
        mixed $value,
        int   $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
        int   $depth = 512
    ): string
    {
        return json_encode($value, $flags, $depth);
    }

    /**
     * Decode a JSON string to a value.
     *
     * @param string $json The JSON string.
     * @param bool $associative Return as associative array instead of object.
     * @param int $flags JSON decoding flags.
     * @param int $depth Maximum depth.
     *
     * @return mixed The decoded value.
     */
    public static function decode(
        string $json,
        bool   $associative = true,
        int    $flags = JSON_THROW_ON_ERROR,
        int    $depth = 512
    ): mixed
    {
        return json_decode($json, $associative, $depth, $flags);
    }

    /**
     * Pretty print JSON string.
     *
     * @param mixed $value The value to encode.
     * @param int $flags Additional JSON encoding flags.
     *
     * @return string The pretty-printed JSON string.
     */
    public static function pretty(
        mixed $value,
        int   $flags = 0
    ): string
    {
        return self::encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | $flags
        );
    }

    /**
     * Encode value to JSON with safe handling (returns null on error).
     *
     * @param mixed $value The value to encode.
     * @param int $flags JSON encoding flags.
     * @param int $depth Maximum depth.
     *
     * @return string|null The JSON string or null on error.
     */
    public static function safeEncode(
        mixed $value,
        int   $flags = JSON_UNESCAPED_UNICODE,
        int   $depth = 512
    ): ?string
    {
        try {
            return json_encode($value, $flags | JSON_THROW_ON_ERROR, $depth);
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * Decode JSON string with safe handling (returns null on error).
     *
     * @param string $json The JSON string.
     * @param bool $associative Return as associative array instead of object.
     * @param int $depth Maximum depth.
     *
     * @return mixed The decoded value or null on error.
     */
    public static function safeDecode(
        string $json,
        bool   $associative = true,
        int    $depth = 512
    ): mixed
    {
        try {
            return json_decode($json, $associative, $depth, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * Validate if a string is valid JSON.
     *
     * @param string $json The string to validate.
     *
     * @return bool True if valid JSON, false otherwise.
     */
    public static function isValid(string $json): bool
    {
        try {
            json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return true;
        } catch (JsonException) {
            return false;
        }
    }

    /**
     * Get the last JSON error message.
     *
     * @return string The error message.
     */
    public static function lastError(): string
    {
        return json_last_error_msg();
    }

    /**
     * Get the last JSON error code.
     *
     * @return int The error code.
     */
    public static function lastErrorCode(): int
    {
        return json_last_error();
    }

    /**
     * Read and decode JSON from a file.
     *
     * @param string $path The file path.
     * @param bool $associative Return as associative array instead of object.
     *
     * @return mixed The decoded value.
     * @throws JsonException If file cannot be read or JSON is invalid.
     */
    public static function fromFile(string $path, bool $associative = true): mixed
    {
        if (!file_exists($path)) {
            throw new JsonException("File not found: {$path}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new JsonException("Unable to read file: {$path}");
        }

        return self::decode($content, $associative);
    }

    /**
     * Encode and write JSON to a file.
     *
     * @param string $path The file path.
     * @param mixed $value The value to encode.
     * @param bool $pretty Whether to pretty print.
     * @param int $flags Additional JSON encoding flags.
     *
     * @return bool True on success.
     * @throws JsonException If encoding or writing fails.
     */
    public static function toFile(
        string $path,
        mixed  $value,
        bool   $pretty = false,
        int    $flags = 0
    ): bool
    {
        $json = $pretty
            ? self::pretty($value, $flags)
            : self::encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | $flags);

        $result = file_put_contents($path, $json);
        if ($result === false) {
            throw new JsonException("Unable to write file: {$path}");
        }

        return true;
    }

    /**
     * Merge multiple JSON strings or arrays.
     *
     * @param string|array ...$jsons JSON strings or arrays to merge.
     *
     * @return array The merged array.
     * @throws JsonException If any JSON is invalid.
     */
    public static function merge(string|array ...$jsons): array
    {
        if (empty($jsons)) {
            return [];
        }

        // Decode all JSON strings first
        $arrays = [];
        foreach ($jsons as $json) {
            $data = is_string($json) ? self::decode($json, true) : $json;

            if (!is_array($data)) {
                throw new JsonException("Cannot merge non-array JSON values");
            }

            $arrays[] = $data;
        }

        // Use spread operator for efficient merging
        return array_merge(...$arrays);
    }

    /**
     * Deep merge multiple JSON strings or arrays.
     *
     * @param string|array ...$jsons JSON strings or arrays to merge.
     *
     * @return array The deeply merged array.
     * @throws JsonException If any JSON is invalid.
     */
    public static function mergeDeep(string|array ...$jsons): array
    {
        if (empty($jsons)) {
            return [];
        }

        // Decode all JSON strings first
        $arrays = [];
        foreach ($jsons as $json) {
            $data = is_string($json) ? self::decode($json, true) : $json;

            if (!is_array($data)) {
                throw new JsonException("Cannot merge non-array JSON values");
            }

            $arrays[] = $data;
        }

        // Merge all at once instead of in a loop
        $result = array_shift($arrays);
        foreach ($arrays as $array) {
            $result = self::arrayMergeRecursive($result, $array);
        }

        return $result;
    }

    /**
     * Recursively merge arrays.
     *
     * @param array $array1 First array.
     * @param array $array2 Second array.
     *
     * @return array The merged array.
     */
    private static function arrayMergeRecursive(array $array1, array $array2): array
    {
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                $array1[$key] = self::arrayMergeRecursive($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }

        return $array1;
    }

    /**
     * Get a value from JSON using dot notation.
     *
     * @param string $json The JSON string.
     * @param string $key The dot notation key (e.g., "user.address.city").
     * @param mixed $default The default value if key not found.
     *
     * @return mixed The value or default.
     */
    public static function get(string $json, string $key, mixed $default = null): mixed
    {
        $data = self::decode($json, true);
        return self::getArrayValue($data, $key, $default);
    }

    /**
     * Set a value in JSON using dot notation.
     *
     * @param string $json The JSON string.
     * @param string $key The dot notation key.
     * @param mixed $value The value to set.
     *
     * @return string The updated JSON string.
     * @throws JsonException If JSON is invalid.
     */
    public static function set(string $json, string $key, mixed $value): string
    {
        $data = self::decode($json, true);
        self::setArrayValue($data, $key, $value);
        return self::encode($data);
    }

    /**
     * Check if a key exists in JSON using dot notation.
     *
     * @param string $json The JSON string.
     * @param string $key The dot notation key.
     *
     * @return bool True if key exists, false otherwise.
     */
    public static function has(string $json, string $key): bool
    {
        $data = self::decode($json, true);
        return self::hasArrayKey($data, $key);
    }

    /**
     * Remove a key from JSON using dot notation.
     *
     * @param string $json The JSON string.
     * @param string $key The dot notation key.
     *
     * @return string The updated JSON string.
     */
    public static function forget(string $json, string $key): string
    {
        $data = self::decode($json, true);
        self::unsetArrayValue($data, $key);
        return self::encode($data);
    }

    /**
     * Get value from array using dot notation.
     *
     * @param array $array The array.
     * @param string $key The dot notation key.
     * @param mixed $default The default value.
     *
     * @return mixed The value or default.
     */
    private static function getArrayValue(array $array, string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return $default;
            }
            $current = $current[$k];
        }

        return $current;
    }

    /**
     * Set value in array using dot notation.
     *
     * @param array &$array The array (by reference).
     * @param string $key The dot notation key.
     * @param mixed $value The value to set.
     *
     * @return void
     */
    private static function setArrayValue(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * Check if key exists in array using dot notation.
     *
     * @param array $array The array.
     * @param string $key The dot notation key.
     *
     * @return bool True if exists, false otherwise.
     */
    private static function hasArrayKey(array $array, string $key): bool
    {
        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return false;
            }
            $current = $current[$k];
        }

        return true;
    }

    /**
     * Unset value in array using dot notation.
     *
     * @param array &$array The array (by reference).
     * @param string $key The dot notation key.
     *
     * @return void
     */
    private static function unsetArrayValue(array &$array, string $key): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        $lastKey = array_pop($keys);

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                return;
            }
            $current = &$current[$k];
        }

        unset($current[$lastKey]);
    }

    /**
     * Minify JSON by removing unnecessary whitespace.
     *
     * @param string $json The JSON string.
     *
     * @return string The minified JSON.
     */
    public static function minify(string $json): string
    {
        return self::encode(self::decode($json));
    }

    /**
     * Format JSON string to be pretty printed.
     *
     * @param string $json The JSON string.
     *
     * @return string The pretty-printed JSON.
     */
    public static function format(string $json): string
    {
        return self::pretty(self::decode($json));
    }

    /**
     * Convert JSON to XML.
     *
     * @param string $json The JSON string.
     * @param string $rootElement The root element name.
     *
     * @return string The XML string.
     */
    public static function toXml(string $json, string $rootElement = 'root'): string
    {
        $data = self::decode($json, true);
        $xml = new SimpleXMLElement("<{$rootElement}/>");
        self::arrayToXml($data, $xml);
        return $xml->asXML();
    }

    /**
     * Recursively convert array to XML.
     *
     * @param array $data The data array.
     * @param SimpleXMLElement $xml The XML element.
     *
     * @return void
     */
    private static function arrayToXml(array $data, SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = "item$key";
            }

            if (is_array($value)) {
                $child = $xml->addChild($key);
                self::arrayToXml($value, $child);
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }
    }

    /**
     * Convert JSON to CSV.
     *
     * @param string $json The JSON string (must be array of objects/arrays).
     * @param bool $includeHeaders Whether to include header row.
     *
     * @return string The CSV string.
     * @throws JsonException If JSON is invalid or not array format.
     */
    public static function toCsv(string $json, bool $includeHeaders = true): string
    {
        $data = self::decode($json, true);

        if (!is_array($data) || empty($data)) {
            return '';
        }

        // Ensure we have an array of arrays
        $rows = array_values($data);
        if (!is_array($rows[0])) {
            throw new JsonException("JSON must be an array of objects/arrays for CSV conversion");
        }

        $output = fopen('php://temp', 'rb+');

        if ($includeHeaders) {
            fputcsv($output, array_keys($rows[0]));
        }

        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Validate JSON against a schema (basic validation).
     *
     * @param string $json The JSON string to validate.
     * @param array $schema The validation schema.
     *
     * @return bool True if valid, false otherwise.
     */
    public static function validate(string $json, array $schema): bool
    {
        $data = self::decode($json, true);
        return self::validateData($data, $schema);
    }

    /**
     * Basic schema validation for data.
     *
     * @param mixed $data The data to validate.
     * @param array $schema The validation rules.
     *
     * @return bool True if valid, false otherwise.
     */
    private static function validateData(mixed $data, array $schema): bool
    {
        foreach ($schema as $key => $rules) {
            if (!is_array($data) || !array_key_exists($key, $data)) {
                if (isset($rules['required']) && $rules['required']) {
                    return false;
                }
                continue;
            }

            $value = $data[$key];

            if (isset($rules['type'])) {
                $expectedType = $rules['type'];
                $actualType = gettype($value);

                if ($expectedType === 'array' && !is_array($value)) {
                    return false;
                }
                if ($expectedType === 'string' && !is_string($value)) {
                    return false;
                }
                if ($expectedType === 'integer' && !is_int($value)) {
                    return false;
                }
                if ($expectedType === 'boolean' && !is_bool($value)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Extract specific keys from JSON.
     *
     * @param string $json The JSON string.
     * @param array $keys The keys to extract.
     *
     * @return string The JSON with only specified keys.
     * @throws JsonException If JSON is invalid.
     */
    public static function only(string $json, array $keys): string
    {
        $data = self::decode($json, true);
        $result = array_intersect_key($data, array_flip($keys));
        return self::encode($result);
    }

    /**
     * Remove specific keys from JSON.
     *
     * @param string $json The JSON string.
     * @param array $keys The keys to remove.
     *
     * @return string The JSON without specified keys.
     * @throws JsonException If JSON is invalid.
     */
    public static function except(string $json, array $keys): string
    {
        $data = self::decode($json, true);
        $result = array_diff_key($data, array_flip($keys));
        return self::encode($result);
    }

    /**
     * Flatten a nested JSON structure.
     *
     * @param string $json The JSON string.
     * @param string $separator The separator for flattened keys.
     *
     * @return string The flattened JSON.
     * @throws JsonException If JSON is invalid.
     */
    public static function flatten(string $json, string $separator = '.'): string
    {
        $data = self::decode($json, true);
        $result = self::flattenArray($data, '', $separator);
        return self::encode($result);
    }

    /**
     * Recursively flatten an array.
     *
     * @param array $array The array to flatten.
     * @param string $prefix The key prefix.
     * @param string $separator The separator.
     *
     * @return array The flattened array.
     */
    private static function flattenArray(array $array, string $prefix = '', string $separator = '.'): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . $separator . $key;

            if (is_array($value)) {
                // Directly merge instead of array_merge in loop
                foreach (self::flattenArray($value, $newKey, $separator) as $k => $v) {
                    $result[$k] = $v;
                }
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}