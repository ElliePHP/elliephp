<?php

namespace ElliePHP\Components\Cache;

use DateInterval;
use DateTime;

final readonly class Shared
{
    /**
     * Convert TTL to seconds
     * Returns 0 for null (meaning no expiry/forever)
     * Returns the max int value for values exceeding it
     */
    public static function convertTtlToSeconds(null|int|DateInterval $ttl): int
    {
        if ($ttl === null) {
            return 0;
        }

        if ($ttl instanceof DateInterval) {
            $now = new DateTime();
            $future = new DateTime()->add($ttl);
            $seconds = $future->getTimestamp() - $now->getTimestamp();
            return \max(0, \min($seconds, 2147483647));
        }

        // Validate and clamp TTL value
        if ($ttl < 0) {
            return 0;
        }

        return \min($ttl, 2147483647);
    }
    
    /**
     * Validate cache key according to PSR-16
     * @throws \ElliePHP\Components\Cache\Exceptions\InvalidArgumentException
     */
    public static function validateKey(string $key): void
    {
        if ($key === '') {
            throw new \ElliePHP\Components\Cache\Exceptions\InvalidArgumentException('Cache key cannot be empty');
        }
        
        if (\strlen($key) > 255) {
            throw new \ElliePHP\Components\Cache\Exceptions\InvalidArgumentException('Cache key cannot exceed 255 characters');
        }
        
        if (\preg_match('/[{}()\\/\\\\@:]/', $key)) {
            throw new \ElliePHP\Components\Cache\Exceptions\InvalidArgumentException('Cache key contains reserved characters: {}()/\\@:');
        }
    }

    /**
     * Convert iterable to array
     */
    public static function iterableToArray(iterable $iterable): array
    {
        if (is_array($iterable)) {
            return $iterable;
        }

        return iterator_to_array($iterable);
    }

}