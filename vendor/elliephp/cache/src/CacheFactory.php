<?php

namespace ElliePHP\Components\Cache;

use ElliePHP\Components\Cache\Contracts\CacheInterface;
use ElliePHP\Components\Cache\Drivers\ApcuDriver;
use ElliePHP\Components\Cache\Drivers\FileDriver;
use ElliePHP\Components\Cache\Drivers\RedisDriver;
use ElliePHP\Components\Cache\Drivers\SQLiteDriver;
use ElliePHP\Components\Cache\Exceptions\CacheException;
use ElliePHP\Components\Support\Util\Str;
use PDO;
use Predis\Client;
use Throwable;

final readonly class CacheFactory
{
    /**
     * Creates a cache driver instance based on configuration.
     *
     * @param string|null $driver The cache driver type (redis, sqlite, file, apcu)
     * @param array<string, mixed> $config Configuration options for the driver
     * @throws CacheException
     */
    public static function create(?string $driver = null, array $config = []): CacheInterface
    {
        $driver = Str::toLowerCase($driver ?? CacheDrivers::FILE);

        return match ($driver) {
            CacheDrivers::REDIS, CacheDrivers::VALKEY => self::createRedisDriver($config),
            CacheDrivers::SQLITE => self::createSQLiteDriver($config),
            CacheDrivers::FILE => self::createFileDriver($config),
            CacheDrivers::APCU => self::createApcuDriver(),
            default => throw new CacheException("Invalid cache driver specified: $driver"),
        };
    }

    /**
     * Creates a Redis cache driver.
     *
     * @param array<string, mixed> $config Configuration options:
     *   - host: Redis host (default: '127.0.0.1')
     *   - port: Redis port (default: 6379)
     *   - password: Redis password (default: null)
     *   - database: Redis database number (default: 0)
     *   - timeout: Connection timeout (default: 5.0)
     *   - prefix: Key prefix (default: '')
     * @throws CacheException
     */
    public static function createRedisDriver(array $config = []): RedisDriver
    {
        try {
            $clientConfig = [
                'host' => $config['host'] ?? '127.0.0.1',
                'port' => $config['port'] ?? 6379,
                'database' => $config['database'] ?? 0,
                'timeout' => $config['timeout'] ?? 5.0,
            ];

            if (!empty($config['password'])) {
                $clientConfig['password'] = $config['password'];
            }

            if (!empty($config['prefix'])) {
                $clientConfig['prefix'] = $config['prefix'];
            }

            $client = new Client($clientConfig);
            $client->connect();

            return new RedisDriver($client);
        } catch (Throwable $e) {
            throw new CacheException('Failed to initialize Redis driver: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Creates a SQLite cache driver.
     *
     * @param array<string, mixed> $config Configuration options:
     *   - path: Path to SQLite database file (required)
     *   - create_directory: Auto-create directory if not exists (default: true)
     *   - directory_permissions: Directory permissions (default: 0755)
     * @throws CacheException
     */
    public static function createSQLiteDriver(array $config = []): SQLiteDriver
    {
        try {
            if (empty($config['path'])) {
                throw new CacheException('SQLite cache driver requires a "path" configuration.');
            }

            $path = $config['path'];
            $createDirectory = $config['create_directory'] ?? true;
            $permissions = $config['directory_permissions'] ?? 0755;

            // Ensure directory exists
            if ($createDirectory) {
                $directory = dirname($path);
                if (!is_dir($directory) && !mkdir($directory, $permissions, true) && !is_dir($directory)) {
                    throw new CacheException(sprintf('Directory "%s" could not be created.', $directory));
                }
            }

            $pdo = new PDO('sqlite:' . $path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return new SQLiteDriver($pdo);
        } catch (CacheException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new CacheException('Failed to initialize SQLite driver: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Creates a File cache driver.
     *
     * @param array<string, mixed> $config Configuration options:
     *   - path: Path to cache directory (required)
     *   - create_directory: Auto-create directory if not exists (default: true)
     *   - directory_permissions: Directory permissions (default: 0755)
     * @throws CacheException
     */
    public static function createFileDriver(array $config = []): FileDriver
    {
        try {
            if (empty($config['path'])) {
                throw new CacheException('File cache driver requires a "path" configuration.');
            }

            $path = rtrim($config['path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $createDirectory = $config['create_directory'] ?? true;
            $permissions = $config['directory_permissions'] ?? 0755;

            // Ensure directory exists
            if ($createDirectory && !is_dir($path) && !mkdir($path, $permissions, true) && !is_dir($path)) {
                throw new CacheException(sprintf('Directory "%s" could not be created.', $path));
            }

            if (!is_dir($path)) {
                throw new CacheException(sprintf('Cache directory "%s" does not exist.', $path));
            }

            if (!is_writable($path)) {
                throw new CacheException(sprintf('Cache directory "%s" is not writable.', $path));
            }

            return new FileDriver($path);
        } catch (CacheException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new CacheException('Failed to initialize File driver: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Creates an APCu cache driver.
     *
     * @throws CacheException
     */
    public static function createApcuDriver(): ApcuDriver
    {
        return new ApcuDriver();
    }
}