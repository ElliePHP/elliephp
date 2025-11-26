<?php

namespace ElliePHP\ElliePHP\Support;

use DI\Container as DIContainer;
use DI\ContainerBuilder;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Container wrapper for PHP-DI
 *
 * Provides a simple interface to the dependency injection container.
 * Singleton pattern ensures single container instance throughout the application.
 */
final class Container
{
    private static ?ContainerInterface $instance = null;

    /**
     * Build and return the container instance
     * @throws Exception
     */
    public static function getInstance(): ContainerInterface
    {
        if (self::$instance === null) {
            self::$instance = self::build();
        }

        return self::$instance;
    }

    /**
     * Build the container with configuration
     * @throws Exception
     */
    private static function build(): ContainerInterface
    {
        $builder = new ContainerBuilder();

        // Enable compilation for production
        if (env('APP_ENV') === 'production') {
            $builder->enableCompilation(storage_cache_path());
            $builder->writeProxiesToFile(true, storage_cache_path() . '/proxies');
        }

        // Load container definitions
        $definitions = [];
        $configFile = root_path('configs/Container.php');

        if (file_exists($configFile)) {
            $definitions = require $configFile;
        }

        $builder->addDefinitions($definitions);

        return $builder->build();
    }

    /**
     * Resolve a service from the container
     * @throws Exception|ContainerExceptionInterface
     */
    public static function get(string $id): mixed
    {
        return self::getInstance()->get($id);
    }

    /**
     * Check if container has a service
     * @throws Exception
     */
    public static function has(string $id): bool
    {
        return self::getInstance()->has($id);
    }

    /**
     * Make a new instance with dependencies resolved
     * @throws Exception
     * @throws ContainerExceptionInterface
     */
    public static function make(string $class, array $parameters = []): mixed
    {
        $container = self::getInstance();

        if ($container instanceof DIContainer) {
            return $container->make($class, $parameters);
        }

        return $container->get($class);
    }
}
