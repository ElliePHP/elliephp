<?php

use ElliePHP\Components\Cache\Cache;
use ElliePHP\Components\Cache\CacheDrivers;
use ElliePHP\Components\Cache\CacheFactory;
use ElliePHP\Components\Support\Http\Request;
use ElliePHP\Components\Support\Http\Response;
use ElliePHP\Components\Support\Logging\Log;
use ElliePHP\Components\Support\Parsers\ConfigParser;
use ElliePHP\Components\Support\Util\Env;
use ElliePHP\Components\Support\Util\Str;
use ElliePHP\ElliePHP\Support\Container;
use ElliePHP\Framework\Database\Database;
use ElliePHP\Framework\Exceptions\DatabaseException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LogLevel;

/**
 * Get environment variable value or Env instance
 *
 * @param string|null $value Environment variable name
 * @param mixed $defaultValue Default value if variable doesn't exist
 * @return mixed Environment value or Env instance
 */
function env(?string $value = null, mixed $defaultValue = null): mixed
{
    static $instance = null;

    if ($instance === null) {
        $instance = new Env(root_path());
    }

    if ($value === null) {
        return $instance;
    }

    return $instance->get($value, $defaultValue);
}

/**
 * Get the Log instance for application logging
 *
 * @return Log Logger instance with app and exception channels
 */
function report(): Log
{
    static $instance = null;

    if ($instance === null) {
        $appLogger = new Logger('app');
        $appLogger->pushHandler(
            new StreamHandler(storage_logs_path('app.log'), LogLevel::DEBUG)
        );

        $exceptionLogger = new Logger('exceptions');
        $exceptionLogger->pushHandler(
            new StreamHandler(storage_logs_path('exceptions.log'), LogLevel::CRITICAL)
        );

        $instance = new Log($appLogger, $exceptionLogger);
    }

    return $instance;
}

/**
 * Get the current HTTP request instance
 *
 * @return Request PSR-7 ServerRequest instance
 */
function request(): Request
{
    static $instance = null;

    if ($instance === null) {
        $instance = Request::fromGlobals();
    }

    return $instance;
}

/**
 * Create a new HTTP response instance
 *
 * @param int $status HTTP status code (default: 200)
 * @return Response PSR-7 Response instance
 */
function response(int $status = 200): Response
{
    static $factory = null;

    if ($factory === null) {
        $factory = new Psr17Factory();
    }

    $psrResponse = $factory->createResponse($status);
    return new Response($psrResponse);
}

/**
 * Get the container instance or resolve a service
 *
 * @param string|null $abstract Service identifier to resolve
 * @return mixed
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 * @throws Exception
 */
function container(?string $abstract = null): mixed
{
    $container = Container::getInstance();
    
    if ($abstract === null) {
        return $container;
    }
    
    return $container->get($abstract);
}


/**
 * Get a Cache instance with the specified driver
 *
 * Supported drivers: redis, sqlite, file (default)
 *
 * @param string|null $cacheDriver Cache driver name (uses CACHE_DRIVER env if null)
 * @return Cache Cache instance with configured driver
 */
function cache(?string $cacheDriver = null): Cache
{
    $driver = $cacheDriver ?? env('CACHE_DRIVER', CacheDrivers::FILE);

    $driverInstance = match (Str::toLowerCase($driver)) {
        'redis' => CacheFactory::createRedisDriver([
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => env('REDIS_DATABASE', 0),
            'timeout' => env('REDIS_TIMEOUT', 5),
        ]),

        'sqlite' => CacheFactory::createSQLiteDriver([
            'path' => storage_cache_path('cache.db'),
            'create_directory' => true,
            'directory_permissions' => 0755,
        ]),

        default => CacheFactory::createFileDriver([
            'path' => storage_cache_path()
        ])
    };

    // Clear expired entries if the driver supports it
    if (method_exists($driverInstance, 'clearExpired')) {
        $driverInstance->clearExpired();
    }

    return new Cache($driverInstance);
}


/**
 * Get or set configuration values using dot notation
 *
 * @param array|string|null $key Configuration key or array of key-value pairs to set
 * @param mixed $default Default value if key doesn't exist
 * @return mixed Configuration value, ConfigParser instance, or null
 */
function config(array|string|null $key = null, mixed $default = null): mixed
{
    static $instance = null;

    if ($instance === null) {
        $configPath = root_path('/configs');
        $instance = new ConfigParser($configPath);

        try {
            $instance->loadAll();
        } catch (Exception $e) {
            report()->exception($e);
        }
    }

    // Lazy load config file if not already loaded
    if (is_string($key) && !$instance->has($key)) {
        $configFile = explode('.', $key)[0];
        try {
            $instance->load($configFile);
        } catch (Exception $e) {
            report()->exception($e);
        }
    }

    // Return ConfigParser instance
    if ($key === null) {
        return $instance;
    }

    // Set multiple config values
    if (is_array($key)) {
        foreach ($key as $k => $v) {
            $instance->set($k, $v);
        }
        return $instance;
    }

    // Get single config value
    return $instance->get($key, $default);
}


function db(): Database
{
    static $db;

    if (!$db || !$db->isConnected()) {
        try {
            $db = new Database([
                "host" => env("DB_HOST")->string(),
                "dbname" => env("DB_NAME")->string(),
                "username" => env("DB_USER")->string(),
                "password" => env("DB_PASS")->string(),
            ]);
        } catch (Throwable $e) {
                report()->error("Failed to initialize database connection", [
                    "exception" => $e->getMessage(),
                ]);
            throw new DatabaseException(
                "Failed to initialize database connection.",
                $e->getCode(),
                $e,
            );
        }
    }

    return $db;
}
