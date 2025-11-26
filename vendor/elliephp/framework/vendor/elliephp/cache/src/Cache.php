<?php

namespace ElliePHP\Components\Cache;

use DateInterval;
use ElliePHP\Components\Cache\Contracts\CacheInterface;
use ElliePHP\Components\Support\Util\Str;

/**
 * The main Cache class that acts as a proxy to the configured cache driver.
 * It consistently applies a prefix to all keys and validates them according to PSR-16.
 */
final class Cache implements CacheInterface
{
    private const int MAX_CACHE_TTL = 2147483647;
    private const string CACHE_KEY = 'ellie_cache:';

    public function __construct(private readonly CacheInterface $driver)
    {
    }

    /**
     * Applies the global prefix to a given key.
     */
    private function getPrefixedKey(string $key): string
    {
        return self::CACHE_KEY . $key;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        Shared::validateKey($key);
        return $this->driver->get($this->getPrefixedKey($key), $default);
    }

    public function set(string $key, mixed $value, int|DateInterval|null $ttl = self::MAX_CACHE_TTL): bool
    {
        Shared::validateKey($key);
        
        if ($value === "") {
            return false;
        }

        return $this->driver->set($this->getPrefixedKey($key), $value, $ttl);
    }

    public function delete(string $key): bool
    {
        Shared::validateKey($key);
        return $this->driver->delete($this->getPrefixedKey($key));
    }

    public function has(string $key): bool
    {
        Shared::validateKey($key);
        return $this->driver->has($this->getPrefixedKey($key));
    }

    /**
     * Wipes clean the entire cache's storage for the current driver.
     * Note: This is a global operation and is not affected by the prefix.
     * It will delete ALL keys in the current database/directory, not just prefixed ones.
     */
    public function clear(): bool
    {
        return $this->driver->clear();
    }

    /**
     * Gets the total number of items in the cache.
     * Note: This is a global operation and will count ALL items, not just prefixed ones.
     */
    public function count(): int
    {
        return $this->driver->count();
    }

    /**
     * Gets the total size of the cache in bytes.
     * Note: This is a global operation and will measure the size of the entire cache.
     */
    public function size(): int
    {
        return $this->driver->size();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $prefixedKeys = [];
        foreach ($keys as $key) {
            Shared::validateKey($key);
            $prefixedKeys[] = $this->getPrefixedKey($key);
        }
        
        $results = $this->driver->getMultiple($prefixedKeys, $default);
        
        // Remove prefix from result keys
        $unprefixedResults = [];
        foreach ($results as $prefixedKey => $value) {
            $originalKey = Str::substr($prefixedKey, Str::length(self::CACHE_KEY));
            $unprefixedResults[$originalKey] = $value;
        }
        
        return $unprefixedResults;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $prefixedValues = [];
        foreach ($values as $key => $value) {
            Shared::validateKey($key);
            $prefixedValues[$this->getPrefixedKey($key)] = $value;
        }
        
        return $this->driver->setMultiple($prefixedValues, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $prefixedKeys = [];
        foreach ($keys as $key) {
            Shared::validateKey($key);
            $prefixedKeys[] = $this->getPrefixedKey($key);
        }
        
        return $this->driver->deleteMultiple($prefixedKeys);
    }
}
