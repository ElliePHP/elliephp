<?php

namespace ElliePHP\Components\Cache\Drivers;

use ElliePHP\Components\Cache\Contracts\CacheInterface;
use ElliePHP\Components\Cache\Exceptions\CacheException;
use ElliePHP\Components\Cache\Shared;
use ElliePHP\Components\Support\Traits\Types;
use ElliePHP\Components\Support\Util\File;
use ElliePHP\Components\Support\Util\Hash;
use DateInterval;
use ElliePHP\Components\Support\Util\Json;
use Throwable;

final readonly class FileDriver implements CacheInterface
{
    use Types;

    private const string DOT_JSON = '.json';

    public function __construct(private string $cachePath)
    {
        $this->ensureDirectoryExists($this->cachePath);
    }

    private function getFilePath(string $key): string
    {
        return $this->cachePath . Hash::xxh3($key) . self::DOT_JSON;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->getFilePath($key);

        if (!File::exists($path)) {
            return $default;
        }

        try {
            $data = File::get($path);
            $cacheData = Json::decode($data);
        } catch (Throwable) {
            $this->delete($key);
            return $default;
        }

        if ($cacheData['expires_at'] !== 0 && \time() > $cacheData['expires_at']) {
            $this->delete($key);
            return $default;
        }

        return $cacheData['content'];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        
        try {
            $path = $this->getFilePath($key);
            $ttlSeconds = Shared::convertTtlToSeconds($ttl);
            // 0 means no expiry (forever)
            $expiresAt = ($ttlSeconds === 0) ? 0 : \time() + $ttlSeconds;

            $cacheData = [
                'content' => $value,
                'expires_at' => $expiresAt,
            ];

            File::putJson($path, $cacheData, 0);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function delete(string $key): bool
    {
        $path = $this->getFilePath($key);
        return File::delete($path);
    }

    public function clear(): bool
    {
        try {
            $files = File::glob($this->cachePath . '*' . self::DOT_JSON);

            if (empty($files)) {
                return true;
            }

            $success = true;
            foreach ($files as $file) {
                if (File::isFile($file) && !File::delete($file)) {
                    $success = false;
                }
            }
            return $success;
        } catch (Throwable) {
            return false;
        }
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function count(): int
    {
        try {
            $files = File::glob($this->cachePath . '*' . self::DOT_JSON);
            return \count($files);
        } catch (Throwable) {
            return 0;
        }
    }

    public function size(): int
    {
        try {
            $totalSize = 0;
            $files = File::glob($this->cachePath . '*' . self::DOT_JSON);

            foreach ($files as $file) {
                if (File::isFile($file)) {
                    try {
                        $totalSize += File::size($file);
                    } catch (Throwable) {
                        continue;
                    }
                }
            }

            return $totalSize;
        } catch (Throwable) {
            return 0;
        }
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

        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
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
            if (!$this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }
    
    /**
     * Clears expired cache files
     */
    public function clearExpired(): int
    {
        try {
            $files = File::glob($this->cachePath . '*' . self::DOT_JSON);
            $cleared = 0;

            foreach ($files as $file) {
                if (!File::isFile($file)) {
                    continue;
                }

                try {
                    $data = File::get($file);
                    $cacheData = Json::decode($data);
                    
                    if ($cacheData['expires_at'] !== 0 && \time() > $cacheData['expires_at']) {
                        if (File::delete($file)) {
                            $cleared++;
                        }
                    }
                } catch (Throwable) {
                    continue;
                }
            }

            return $cleared;
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @throws CacheException
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!File::makeDirectory($path)) {
            throw new CacheException(
                \sprintf('Directory "%s" could not be created.', $path),
            );
        }
    }
}