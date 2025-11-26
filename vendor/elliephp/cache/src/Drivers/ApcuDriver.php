<?php

namespace ElliePHP\Components\Cache\Drivers;

use DateInterval;
use ElliePHP\Components\Cache\Contracts\CacheInterface;
use ElliePHP\Components\Cache\Exceptions\CacheException;
use ElliePHP\Components\Cache\Shared;

final readonly class ApcuDriver implements CacheInterface
{
    public function __construct()
    {
        if (!extension_loaded('apcu')) {
            throw new CacheException('APCu extension is not available.');
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = apcu_fetch($key, $success);
        return $success ? $value : $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $ttlSeconds = Shared::convertTtlToSeconds($ttl);
        return apcu_store($key, $value, $ttlSeconds);
    }

    public function delete(string $key): bool
    {
        return apcu_delete($key);
    }

    public function clear(): bool
    {
        return apcu_clear_cache();
    }

    public function has(string $key): bool
    {
        return apcu_exists($key);
    }

    public function count(): int
    {
        $info = apcu_cache_info();
        return $info['num_entries'] ?? 0;
    }

    public function size(): int
    {
        $info = apcu_cache_info();
        return (int)($info['mem_size'] ?? 0);
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keys = Shared::iterableToArray($keys);
        
        foreach ($keys as $key) {
        }
        
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $values = Shared::iterableToArray($values);
        
        foreach (\array_keys($values) as $key) {
        }
        
        $ttlSeconds = Shared::convertTtlToSeconds($ttl);

        $success = true;
        foreach ($values as $key => $value) {
            if (!apcu_store($key, $value, $ttlSeconds)) {
                $success = false;
            }
        }

        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $keys = Shared::iterableToArray($keys);
        
        foreach ($keys as $key) {
        }

        $success = true;
        foreach ($keys as $key) {
            if (!apcu_delete($key)) {
                $success = false;
            }
        }

        return $success;
    }

}