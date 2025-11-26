<?php

namespace ElliePHP\Bootstrap;

use ElliePHP\Components\Routing\Router;
use Laminas\Diactoros\ServerRequestFactory;

/**
 * HTTP application kernel
 *
 * Handles HTTP request lifecycle: bootstrapping, routing, and response emission.
 * Singleton pattern ensures single application instance per request.
 */
final class HttpApplication extends Kernel
{
    public const string VERSION = '1.0.0';

    private static ?self $instance = null;

    public static function init(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(): void
    {
        $this->bootstrap();

        $request = ServerRequestFactory::fromGlobals();

        $this->configureRouter();

        $response = Router::handle($request);

        $this->emitResponse($response);
    }

    private function configureRouter(): void
    {
        Router::configure([
            'debug_mode' => $this->isDebugMode(),
            'routes_directory' => routes_path(),
            'cache_enabled' => !$this->isDebugMode(),
            'cache_directory' => storage_cache_path(),
            'error_formatter' => config('application.error_formatter'),
            'container' => container(),
            'global_middleware' => config('middleware.global_middlewares'),
        ]);
    }
}