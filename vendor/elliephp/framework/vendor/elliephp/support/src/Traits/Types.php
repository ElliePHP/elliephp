<?php

namespace ElliePHP\Components\Support\Traits;

trait Types
{
    /**
     * Cast a value to string
     *
     * @param mixed $value
     * @return string
     */
    protected function toString(mixed $value): string
    {
        return (string) $value;
    }

    /**
     * Cast a value to integer
     *
     * @param mixed $value
     * @return int
     */
    protected function toInt(mixed $value): int
    {
        return (int) $value;
    }

    /**
     * Cast a value to float
     *
     * @param mixed $value
     * @return float
     */
    protected function toFloat(mixed $value): float
    {
        return (float) $value;
    }

    /**
     * Cast a value to boolean
     *
     * @param mixed $value
     * @return bool
     */
    protected function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Cast a value to array (comma-separated)
     *
     * @param mixed $value
     * @param string $separator
     * @return array
     */
    protected function toArray(mixed $value, string $separator = ','): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        return array_map('trim', explode($separator, (string) $value));
    }

    /**
     * Automatically cast a value based on the default value's type
     *
     * @param mixed $value
     * @param mixed $default
     * @return mixed
     */
    protected function autoCast(mixed $value, mixed $default): mixed
    {
        if ($default === null) {
            return $value;
        }

        return match (gettype($default)) {
            'string' => $this->toString($value),
            'integer' => $this->toInt($value),
            'double' => $this->toFloat($value),
            'boolean' => $this->toBool($value),
            'array' => $this->toArray($value),
            default => $value,
        };
    }
}