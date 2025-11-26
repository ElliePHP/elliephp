<?php

namespace ElliePHP\Components\Cache\Drivers;

use ElliePHP\Components\Cache\Contracts\CacheInterface;
use ElliePHP\Components\Cache\Shared;
use Predis\Client as RedisClient;
use DateInterval;
use Throwable;

final readonly class RedisDriver implements CacheInterface
{
    public function __construct(private RedisClient $redis)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $value = $this->redis->get($key);
            // Redis returns null for non-existent keys
            return $value ?? $default;
        } catch (Throwable) {
            return $default;
        }
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        try {
            $ttlSeconds = Shared::convertTtlToSeconds($ttl);

            if ($ttlSeconds > 0) {
                $result = $this->redis->setex($key, $ttlSeconds, $value);
            } else {
                // 0 means no expiry (forever)
                $result = $this->redis->set($key, $value);
            }

            // Redis SET/SETEX returns 'OK' string on success
            return $result === 'OK' || $result === true;
        } catch (Throwable) {
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            $result = $this->redis->del([$key]);
            return $result > 0;
        } catch (Throwable) {
            return false;
        }
    }

    public function clear(): bool
    {
        try {
            $this->redis->flushdb();
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function has(string $key): bool
    {
        try {
            return (bool)$this->redis->exists($key);
        } catch (Throwable) {
            return false;
        }
    }

    public function count(): int
    {
        try {
            return $this->redis->dbsize();
        } catch (Throwable) {
            return 0;
        }
    }

    public function size(): int
    {
        try {
            $info = $this->redis->info('memory');
            return (int)($info['used_memory'] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keys = Shared::iterableToArray($keys);

        if (empty($keys)) {
            return [];
        }

        try {
            $values = $this->redis->mget($keys);
            $result = [];

            foreach ($keys as $index => $key) {
                $value = $values[$index] ?? null;
                $result[$key] = $value ?? $default;
            }

            return $result;
        } catch (Throwable) {
            // Fallback to individual gets
            $result = [];
            foreach ($keys as $key) {
                $result[$key] = $this->get($key, $default);
            }
            return $result;
        }
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $values = Shared::iterableToArray($values);

        if (empty($values)) {
            return true;
        }

        $ttlSeconds = Shared::convertTtlToSeconds($ttl);
        $success = true;

        try {
            if ($ttlSeconds > 0) {
                // Use pipeline for better performance
                $pipeline = $this->redis->pipeline();
                foreach ($values as $key => $value) {
                    $pipeline->setex($key, $ttlSeconds, $value);
                }
                $results = $pipeline->execute();

                // Check if any operation failed
                foreach ($results as $result) {
                    if ($result !== 'OK' && $result !== true) {
                        $success = false;
                    }
                }
            } else {
                // Use MSET for keys without TTL (forever)
                $this->redis->mset($values);
            }

            return $success;
        } catch (Throwable) {
            return false;
        }
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $keys = Shared::iterableToArray($keys);

        if (empty($keys)) {
            return true;
        }

        try {
            $this->redis->del($keys);
            return true;
        } catch (Throwable) {
            return false;
        }
    }
    
}