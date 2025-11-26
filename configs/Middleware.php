<?php

/**
 * Middleware Configuration
 *
 * Define global middleware that runs on every request.
 * Middleware is executed in the order listed.
 *
 * Add your custom middleware classes to the array below.
 */


use ElliePHP\Application\Middlewares\ApplicationLoggingMiddleware;

return [
    'global_middlewares' => [
        ApplicationLoggingMiddleware::class,
        \ElliePHP\Application\Middlewares\CorsMiddleware::class,
    ],
];