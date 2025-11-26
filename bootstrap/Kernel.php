<?php

namespace ElliePHP\Bootstrap;

use Dotenv\Dotenv;
use JetBrains\PhpStorm\NoReturn;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Base kernel class for application bootstrapping
 *
 * Handles environment loading, error configuration, and exception handling.
 * Extended by HttpApplication for web request handling.
 */
abstract class Kernel
{
    private bool $bootstrapped = false;

    /**
     * Bootstrap the application
     *
     * Loads environment, configures error handling, and prepares the application.
     * Safe to call multiple times - only bootstraps once.
     */
    protected function bootstrap(): void
    {
        if ($this->bootstrapped) {
            return;
        }

        // Register global exception handler first
        $this->registerGlobalExceptionHandler();

        try {
            $this->loadEnvironment();
            $this->configureErrorDisplay();
            $this->configureTimezone();

            $this->bootstrapped = true;

        } catch (Throwable $e) {
            $this->handleBootstrapException($e);
        }
    }

    protected function emitResponse(ResponseInterface $response): void
    {
        new SapiEmitter()->emit($response);
    }

    private function loadEnvironment(): void
    {
        $basePath = root_path();
        $envFile = $basePath . '/.env';

        if (!file_exists($envFile)) {
            throw new RuntimeException(
                "Environment file not found at: $envFile\n" .
                "Please copy .env.example to .env and configure your application."
            );
        }

        $dotenv = Dotenv::createImmutable($basePath);
        $dotenv->load();

        // Validate required environment variables
        $dotenv->required(config('env.required_configs'))->notEmpty();
    }

    private function configureTimezone(): void
    {
        $timezone = env('APP_TIMEZONE', 'UTC');
        date_default_timezone_set($timezone);
    }

    private function configureErrorDisplay(): void
    {
        ini_set('display_errors', '0');
    }

    protected function isDebugMode(): bool
    {
        return config('env.app_env') === 'debug';
    }

    private function registerGlobalExceptionHandler(): void
    {
        set_exception_handler(function (Throwable $e): void {
            $this->handleException($e);
        });
    }

    /**
     * Handles all exceptions during runtime (after bootstrap)
     */
    #[NoReturn]
    private function handleException(Throwable $e): void
    {
        report()->exception($e);

        // CLI handling
        if (PHP_SAPI === 'cli') {
            $prefix = $this->bootstrapped ? "Uncaught exception: " : "Application failed to bootstrap: ";
            echo $prefix . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            exit(1);
        }

        // Web handling - delegate to bootstrap handler if not bootstrapped
        if (!$this->bootstrapped) {
            $this->handleBootstrapException($e);
        }

        // Runtime exception for web requests
        $this->sendErrorResponse($e);
    }

    /**
     * Handles exceptions that occur during bootstrap phase
     * Uses minimal dependencies since the app may not be fully initialized
     */
    #[NoReturn]
    private function handleBootstrapException(Throwable $e): void
    {
        // Try to use configured formatter, fall back to basic error if config unavailable
        try {
            $debugMode = $this->isDebugMode();
            $formatter = config('application.error_formatter');
            $formatted = $formatter->format($e, $debugMode);

            if (!empty($formatted['html'])) {
                $response = new HtmlResponse($formatted['html'], 500);
            } else {
                $response = new JsonResponse($formatted, 500);
            }
        } catch (Throwable) {
            // Config system failed - send basic error
            $response = new HtmlResponse(
                '<h1>Application Error</h1><p>The application failed to start. Please check your configuration.</p>',
                500
            );
        }

        $this->emitResponse($response);
        exit(1);
    }

    /**
     * Send formatted error response for runtime exceptions
     */
    #[NoReturn]
    private function sendErrorResponse(Throwable $e): void
    {
        $formatter = config('application.error_formatter');
        $debugMode = $this->isDebugMode();
        $formatted = $formatter->format($e, $debugMode);

        if (!empty($formatted['html'])) {
            $response = new HtmlResponse($formatted['html'], 500);
        } else {
            $response = new JsonResponse($formatted, 500);
        }

        $this->emitResponse($response);
        exit(1);
    }
}