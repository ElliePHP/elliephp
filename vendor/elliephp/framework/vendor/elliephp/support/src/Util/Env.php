<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Util;

use Dotenv\Dotenv;
use ElliePHP\Components\Support\Traits\Types;

class Env
{
    use Types;

    private Dotenv $dotenv;
    private bool $loaded = false;

    /**
     * Create a new Env instance
     *
     * @param string $path Directory path where .env file is located
     * @param string|array $names Name(s) of the .env file(s) to load
     */
    public function __construct(string $path, string|array $names = '.env')
    {
        $this->dotenv = Dotenv::createImmutable($path, $names);
    }

    /**
     * Check if environment variables have been loaded
     *
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Load the environment variables from .env file
     *
     * @return self
     */
    public function load(): self
    {
        if (!$this->loaded) {
            $this->dotenv->load();
            $this->loaded = true;
        }
        return $this;
    }

    /**
     * Load environment variables and make specified variables required
     *
     * @param array $required Array of required variable names
     * @return self
     */
    public function loadWithRequired(array $required): self
    {
        $this->load();
        $this->dotenv->required($required);
        return $this;
    }

    /**
     * Get an environment variable value with automatic type casting
     * Type is inferred from the default value
     *
     * @param string $key Variable name
     * @param mixed $default Default value if not found (also determines return type)
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Check if variable exists in any source
        $value = null;
        if (isset($_ENV[$key])) {
            $value = $_ENV[$key];
        } elseif (isset($_SERVER[$key])) {
            $value = $_SERVER[$key];
        } else {
            $envValue = getenv($key);
            if ($envValue !== false) {
                $value = $envValue;
            }
        }

        // Variable not found, return default
        if ($value === null) {
            return $default;
        }

        $parsed = $this->parseValue($value);

        // If default is provided, cast to its type
        if ($default !== null) {
            return $this->autoCast($parsed, $default);
        }

        // No default provided - try to intelligently cast the value
        return $this->smartCast($parsed);
    }

    /**
     * Check if an environment variable exists
     *
     * @param string $key Variable name
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_ENV[$key]) || isset($_SERVER[$key]) || getenv($key) !== false;
    }

    /**
     * Get all environment variables
     *
     * @return array
     */
    public function all(): array
    {
        return $_ENV;
    }

    /**
     * Parse environment variable value (convert special string literals).
     * This method handles special cases like '(null)' or '(empty)'.
     * Standard boolean strings 'true' and 'false' are left as strings
     * to be handled by the auto-casting logic, which correctly interprets them.
     *
     * @param mixed $value
     * @return mixed
     */
    private function parseValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // The dotenv library removes outer quotes. If a value was quoted,
        // it's intended to be a string. The special values below are unlikely
        // to be quoted and are safe to convert.

        return match (strtolower($value)) {
            '(true)' => true,
            '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }

    /**
     * Intelligently cast a value based on its content
     *
     * @param mixed $value
     * @return mixed
     */
    private function smartCast(mixed $value): mixed
    {
        // Already a non-string type
        if (!is_string($value)) {
            return $value;
        }

        // Empty string
        if ($value === '') {
            return $value;
        }

        $lower = strtolower($value);

        // Boolean values
        if (in_array($lower, ['true', 'false', 'yes', 'no', 'on', 'off', '1', '0'], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $value;
        }

        // Numeric values
        if (is_numeric($value)) {
            // Check if it's a float
            if (str_contains($value, '.') || str_contains($lower, 'e')) {
                return (float) $value;
            }
            // Integer
            return (int) $value;
        }

        // Return as string
        return $value;
    }

    /**
     * Require specific environment variables to be set
     *
     * @param array $variables
     * @return self
     */
    public function require(array $variables): self
    {
        $this->dotenv->required($variables);
        return $this;
    }

    /**
     * Require environment variables and validate they are not empty
     *
     * @param array $variables
     * @return self
     */
    public function requireNotEmpty(array $variables): self
    {
        $this->dotenv->required($variables)->notEmpty();
        return $this;
    }

    /**
     * Require environment variables to be one of given values
     *
     * @param string $variable
     * @param array $allowedValues
     * @return self
     */
    public function requireOneOf(string $variable, array $allowedValues): self
    {
        $this->dotenv->required($variable)->allowedValues($allowedValues);
        return $this;
    }

}