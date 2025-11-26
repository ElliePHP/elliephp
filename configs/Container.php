<?php

/**
 * Container Configuration
 *
 * Define your service bindings and dependencies here.
 * This file is loaded when the container is built.
 */

use Psr\Container\ContainerInterface;

return [
    // Example: Bind interfaces to implementations
    // UserRepositoryInterface::class => DI\autowire(UserRepository::class),
    
    // Example: Factory definitions
    // 'database' => function (ContainerInterface $c) {
    //     return new PDO(
    //         env('DB_DSN'),
    //         env('DB_USER'),
    //         env('DB_PASS')
    //     );
    // },
    
    // Example: Singleton services
    // CacheService::class => DI\create(CacheService::class)->lazy(),
];
