<?php

namespace ElliePHP\Components\Support\Traits;

use ElliePHP\Components\Support\Util\Json;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use ReflectionIntersectionType;
use InvalidArgumentException;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Traversable;

trait ArraySerializableTrait
{
    /**
     * Create an instance from an array of data.
     *
     * @param array $data The data array.
     *
     * @return static The new instance.
     * @throws ReflectionException If reflection fails.
     * @throws InvalidArgumentException If data is invalid.
     */
    public static function fromArray(array $data): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            $instance = $reflection->newInstanceWithoutConstructor();
            self::hydrateProperties($instance, $data, $reflection);
            return $instance;
        }

        $args = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            // Check if parameter exists in data
            if (!array_key_exists($name, $data)) {
                if ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } elseif ($param->allowsNull()) {
                    $args[] = null;
                } else {
                    throw new InvalidArgumentException(
                        sprintf(
                            "Missing required parameter '%s' for %s",
                            $name,
                            static::class
                        )
                    );
                }
                continue;
            }

            $value = $data[$name];

            // Handle null values
            if ($value === null) {
                if ($param->allowsNull()) {
                    $args[] = null;
                    continue;
                }

                if ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                    continue;
                }

                throw new InvalidArgumentException(
                    sprintf(
                        "Parameter '%s' for %s cannot be null",
                        $name,
                        static::class
                    )
                );
            }

            // Type casting with full support
            $args[] = self::castValue($value, $type, $name);
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * Hydrate object properties directly (for classes without constructor).
     *
     * @param object $instance The instance to hydrate.
     * @param array $data The data array.
     * @param ReflectionClass $reflection The reflection class.
     *
     * @return void
     */
    private static function hydrateProperties(object $instance, array $data, ReflectionClass $reflection): void
    {
        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();

            if (!array_key_exists($name, $data)) {
                continue;
            }

            $type = $property->getType();
            $value = self::castValue($data[$name], $type, $name);
            $property->setValue($instance, $value);
        }
    }

    /**
     * Cast a value to the appropriate type.
     *
     * @param mixed $value The value to cast.
     * @param ReflectionType|null $type The target type.
     * @param string $context Context for error messages.
     *
     * @return mixed The cast value.
     * @throws InvalidArgumentException If casting fails.
     */
    private static function castValue(mixed $value, ?ReflectionType $type, string $context = 'value'): mixed
    {
        if (!$type) {
            return $value;
        }

        // Handle union types (PHP 8.0+)
        if ($type instanceof ReflectionUnionType) {
            return self::castUnionType($value, $type, $context);
        }

        // Handle intersection types (PHP 8.1+)
        if ($type instanceof ReflectionIntersectionType) {
            return self::castIntersectionType($value, $type, $context);
        }

        // Handle named types
        if ($type instanceof ReflectionNamedType) {
            return self::castToNamedType($value, $type, $context);
        }

        return $value;
    }

    /**
     * Cast value to union type (try each type until one succeeds).
     *
     * @param mixed $value The value to cast.
     * @param ReflectionUnionType $type The union type.
     * @param string $context Context for error messages.
     *
     * @return mixed The cast value.
     */
    private static function castUnionType($value, ReflectionUnionType $type, string $context)
    {
        $errors = [];

        foreach ($type->getTypes() as $unionType) {
            try {
                return self::castToNamedType($value, $unionType, $context);
            } catch (\Throwable $e) {
                $errors[] = $unionType->getName() . ': ' . $e->getMessage();
                continue;
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                "Cannot cast %s to union type. Tried: %s",
                $context,
                implode(', ', $errors)
            )
        );
    }

    /**
     * Cast value to intersection type (must satisfy all types).
     *
     * @param mixed $value The value to cast.
     * @param ReflectionIntersectionType $type The intersection type.
     * @param string $context Context for error messages.
     *
     * @return mixed The cast value.
     */
    private static function castIntersectionType(mixed $value, ReflectionIntersectionType $type, string $context): mixed
    {
        if (!is_object($value)) {
            throw new InvalidArgumentException(
                sprintf("Value for %s must be an object for intersection type", $context)
            );
        }

        foreach ($type->getTypes() as $intersectionType) {
            $typeName = $intersectionType->getName();
            if (!is_a($value, $typeName)) {
                throw new InvalidArgumentException(
                    sprintf(
                        "Value for %s must implement %s",
                        $context,
                        $typeName
                    )
                );
            }
        }

        return $value;
    }

    /**
     * Cast value to a specific named type.
     *
     * @param mixed $value The value to cast.
     * @param ReflectionNamedType $type The target type.
     * @param string $context Context for error messages.
     *
     * @return mixed The cast value.
     */
    private static function castToNamedType(mixed $value, ReflectionNamedType $type, string $context): mixed
    {
        $typeName = $type->getName();

        // Handle mixed type (PHP 8.0+)
        if ($typeName === 'mixed') {
            return $value;
        }

        // Handle built-in types
        return match ($typeName) {
            'int' => self::castToInt($value, $context),
            'float' => self::castToFloat($value, $context),
            'string' => self::castToString($value, $context),
            'bool' => self::castToBool($value, $context),
            'array' => self::castToArray($value),
            'object' => self::castToObject($value, $context),
            'callable' => self::castToCallable($value, $context),
            'iterable' => self::castToIterable($value, $context),
            default => self::castToClass($value, $typeName, $type, $context),
        };
    }

    /**
     * Cast to integer with validation.
     */
    private static function castToInt($value, string $context): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value;
        }

        throw new InvalidArgumentException(
            sprintf("Cannot cast %s to int: invalid value", $context)
        );
    }

    /**
     * Cast to float with validation.
     */
    private static function castToFloat($value, string $context): float
    {
        if (is_float($value) || is_int($value)) {
            return (float)$value;
        }

        if (is_numeric($value)) {
            return (float)$value;
        }

        throw new InvalidArgumentException(
            sprintf("Cannot cast %s to float: invalid value", $context)
        );
    }

    /**
     * Cast to string with validation.
     */
    private static function castToString($value, string $context): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            return (string)$value;
        }

        throw new InvalidArgumentException(
            sprintf("Cannot cast %s to string: invalid value", $context)
        );
    }

    /**
     * Cast to boolean with proper string handling.
     */
    private static function castToBool($value, string $context): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        // Handle string representations
        if (is_string($value)) {
            $lower = strtolower(trim($value));

            if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($lower, ['false', '0', 'no', 'off', ''], true)) {
                return false;
            }
        }

        // Use filter_var for other cases
        $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($result === null) {
            throw new InvalidArgumentException(
                sprintf("Cannot cast %s to bool: ambiguous value", $context)
            );
        }

        return $result;
    }

    /**
     * Cast to array.
     */
    private static function castToArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof Traversable) {
            return iterator_to_array($value);
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if (is_object($value)) {
            return get_object_vars($value);
        }

        return [$value];
    }

    /**
     * Cast to object.
     */
    private static function castToObject($value, string $context): object
    {
        if (is_object($value)) {
            return $value;
        }

        if (is_array($value)) {
            return (object)$value;
        }

        throw new InvalidArgumentException(
            sprintf("Cannot cast %s to object", $context)
        );
    }

    /**
     * Cast to callable.
     */
    private static function castToCallable($value, string $context): callable
    {
        if (is_callable($value)) {
            return $value;
        }

        throw new InvalidArgumentException(
            sprintf("Value for %s is not callable", $context)
        );
    }

    /**
     * Cast to iterable.
     */
    private static function castToIterable($value, string $context): iterable
    {
        if (is_iterable($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        throw new InvalidArgumentException(
            sprintf("Cannot cast %s to iterable", $context)
        );
    }

    /**
     * Cast value to a class type.
     */
    private static function castToClass($value, string $className, ReflectionNamedType $type, string $context)
    {
        // If already the correct type, return as-is
        if ($value instanceof $className) {
            return $value;
        }

        // Handle DateTime types specially
        if (in_array($className, [DateTime::class, DateTimeImmutable::class, DateTimeInterface::class])) {
            return self::castToDateTime($value, $className, $context);
        }

        // If class has fromArray method, use it for array data
        if (is_array($value) && method_exists($className, 'fromArray')) {
            return $className::fromArray($value);
        }

        // Try direct instantiation for simple objects
        if (is_array($value) && class_exists($className)) {
            try {
                $reflection = new ReflectionClass($className);

                // Try to use constructor if available
                $constructor = $reflection->getConstructor();
                if (!$constructor || $constructor->getNumberOfRequiredParameters() === 0) {
                    $instance = new $className();

                    // Hydrate properties if possible
                    if (method_exists($instance, 'fromArray')) {
                        return $className::fromArray($value);
                    }

                    return $instance;
                }

                // Try to instantiate with the array data
                return $className::fromArray($value);
            } catch (\Throwable $e) {
                throw new InvalidArgumentException(
                    sprintf(
                        "Cannot cast %s to %s: %s",
                        $context,
                        $className,
                        $e->getMessage()
                    )
                );
            }
        }

        // For built-in classes, return as-is if type matches
        if ($type->isBuiltin()) {
            return $value;
        }

        throw new InvalidArgumentException(
            sprintf(
                "Cannot cast %s to %s: incompatible type",
                $context,
                $className
            )
        );
    }

    /**
     * Cast to DateTime object.
     */
    private static function castToDateTime($value, string $className, string $context): DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (is_string($value) || is_int($value)) {
            try {
                if (is_int($value)) {
                    $value = "@{$value}"; // Unix timestamp
                }

                if ($className === DateTimeImmutable::class) {
                    return new DateTimeImmutable($value);
                }

                return new DateTime($value);
            } catch (Exception $e) {
                throw new InvalidArgumentException(
                    sprintf("Cannot cast %s to DateTime: %s", $context, $e->getMessage())
                );
            }
        }

        throw new InvalidArgumentException(
            sprintf("Cannot cast %s to DateTime", $context)
        );
    }

    /**
     * Convert the instance to an array.
     *
     * @param bool $recursive Whether to recursively convert nested objects.
     * @param int $depth Current recursion depth (for cycle detection).
     * @param array $seen Already seen objects (for cycle detection).
     *
     * @return array The array representation.
     */
    public function toArray(bool $recursive = false, int $depth = 0, array &$seen = []): array
    {
        // Prevent infinite recursion
        if ($depth > 32) {
            return ['_circular_reference' => true];
        }

        $objectHash = spl_object_id($this);
        if (isset($seen[$objectHash])) {
            return ['_circular_reference' => true];
        }

        $seen[$objectHash] = true;

        try {
            $data = get_object_vars($this);

            if (!$recursive) {
                return $data;
            }

            return array_map(function ($value) use ($depth, &$seen) {
                return $this->convertValueToArray($value, $depth, $seen);
            }, $data);
        } finally {
            unset($seen[$objectHash]);
        }
    }

    /**
     * Convert a single value to array representation.
     */
    private function convertValueToArray($value, int $depth, array &$seen)
    {
        // Handle objects with toArray
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray(true, $depth + 1, $seen);
        }

        // Handle DateTime objects
        if ($value instanceof DateTimeInterface) {
            return $value->format('c'); // ISO 8601
        }

        // Handle arrays recursively
        if (is_array($value)) {
            return array_map(function ($item) use ($depth, &$seen) {
                return $this->convertValueToArray($item, $depth + 1, $seen);
            }, $value);
        }

        // Handle other objects
        if (is_object($value)) {
            $objectHash = spl_object_id($value);

            if (isset($seen[$objectHash])) {
                return ['_circular_reference' => true];
            }

            $seen[$objectHash] = true;

            try {
                return array_map(function ($item) use ($depth, &$seen) {
                    return $this->convertValueToArray($item, $depth + 1, $seen);
                }, get_object_vars($value));
            } finally {
                unset($seen[$objectHash]);
            }
        }

        // Return scalar values as-is
        return $value;
    }

    /**
     * Convert the instance to JSON.
     *
     * @param int $flags JSON encoding flags.
     * @param bool $recursive Whether to recursively convert nested objects.
     *
     * @return string The JSON representation.
     * @throws InvalidArgumentException If JSON encoding fails.
     */
    public function toJson(int $flags = JSON_THROW_ON_ERROR, bool $recursive = false): string
    {
        return Json::encode($this->toArray($recursive), $flags);
    }

    /**
     * Create an instance from JSON.
     *
     * @param string $json The JSON string.
     *
     * @return static The new instance.
     * @throws InvalidArgumentException|ReflectionException If JSON is invalid.
     */
    public static function fromJson(string $json): static
    {
        $data = Json::decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new InvalidArgumentException(
                'JSON must decode to an associative array'
            );
        }

        return static::fromArray($data);
    }
}