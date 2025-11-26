<?php

namespace ElliePHP\Components\Cache\Drivers;

use ElliePHP\Components\Cache\Contracts\CacheInterface;
use ElliePHP\Components\Cache\Shared;
use PDO;
use DateInterval;
use Throwable;

final readonly class SQLiteDriver implements CacheInterface
{
    public function __construct(private PDO $pdo)
    {
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS cache (
                id TEXT PRIMARY KEY,
                content TEXT,
                expires_at INTEGER
            )"
        );

        // Create index for faster expiration queries
        $this->pdo->exec(
            "CREATE INDEX IF NOT EXISTS idx_expires_at ON cache(expires_at)"
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        
        try {
            $stmt = $this->pdo->prepare(
                "SELECT content FROM cache WHERE id = :id AND (expires_at = 0 OR expires_at > :now)"
            );
            $stmt->execute([':id' => $key, ':now' => \time()]);

            $result = $stmt->fetchColumn();
            return $result !== false ? $result : $default;
        } catch (Throwable) {
            return $default;
        }
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        
        try {
            $ttlSeconds = Shared::convertTtlToSeconds($ttl);
            // 0 means no expiry (forever)
            $expiresAt = ($ttlSeconds === 0) ? 0 : \time() + $ttlSeconds;

            $stmt = $this->pdo->prepare(
                "REPLACE INTO cache (id, content, expires_at) VALUES (:id, :content, :expires_at)"
            );

            return $stmt->execute([
                ':id' => $key,
                ':content' => $value,
                ':expires_at' => $expiresAt,
            ]);
        } catch (Throwable) {
            return false;
        }
    }

    public function delete(string $key): bool
    {
        
        try {
            return $this->pdo->prepare("DELETE FROM cache WHERE id = :id")->execute([':id' => $key]);
        } catch (Throwable) {
            return false;
        }
    }

    public function clear(): bool
    {
        try {
            // SQLite uses DELETE FROM instead of TRUNCATE
            $this->pdo->exec("DELETE FROM cache");
            return true;
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
            return (int)$this->pdo->query("SELECT COUNT(*) FROM cache WHERE expires_at = 0 OR expires_at > " . \time())->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    public function size(): int
    {
        try {
            // Get database file size in bytes
            $result = $this->pdo->query("SELECT page_count * page_size as size FROM pragma_page_count(), pragma_page_size()")->fetch(PDO::FETCH_ASSOC);
            return (int)($result['size'] ?? 0);
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
        
        foreach ($keys as $key) {
        }

        try {
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $stmt = $this->pdo->prepare(
                "SELECT id, content FROM cache WHERE id IN ($placeholders) AND (expires_at = 0 OR expires_at > ?)"
            );

            $params = array_values($keys);
            $params[] = time();
            $stmt->execute($params);

            $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // Ensure all keys are in the result
            $output = [];
            foreach ($keys as $key) {
                $output[$key] = $results[$key] ?? $default;
            }

            return $output;
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
        
        foreach (array_keys($values) as $key) {
        }

        try {
            $ttlSeconds = Shared::convertTtlToSeconds($ttl);
            // 0 means no expiry (forever)
            $expiresAt = ($ttlSeconds === 0) ? 0 : \time() + $ttlSeconds;

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                "REPLACE INTO cache (id, content, expires_at) VALUES (:id, :content, :expires_at)"
            );

            foreach ($values as $key => $value) {
                $stmt->execute([
                    ':id' => $key,
                    ':content' => $value,
                    ':expires_at' => $expiresAt,
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $keys = Shared::iterableToArray($keys);

        if (empty($keys)) {
            return true;
        }
        
        foreach ($keys as $key) {
        }

        try {
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            return $this->pdo->prepare("DELETE FROM cache WHERE id IN ($placeholders)")->execute(array_values($keys));
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Deletes all expired cache items.
     * Returns the number of items deleted.
     */
    public function clearExpired(): int
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cache WHERE expires_at < :now AND expires_at != 0");
            $stmt->execute([':now' => time()]);
            return $stmt->rowCount();
        } catch (Throwable) {
            return 0;
        }
    }
}