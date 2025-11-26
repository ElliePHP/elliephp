<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Throwable;

/**
 * A facade for a PSR-3 compliant logger.
 */
class Log
{
    private LoggerInterface $logger;
    private LoggerInterface $exceptionLogger;

    /**
     * @param LoggerInterface|null $logger The primary logger for general messages.
     * @param LoggerInterface|null $exceptionLogger A specific logger for exceptions. Falls back to the primary logger if null.
     */
    public function __construct(
        ?LoggerInterface $logger = null,
        ?LoggerInterface $exceptionLogger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->exceptionLogger = $exceptionLogger ?? $this->logger;
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if (!method_exists($this->logger, $level)) {
            $this->logger->error("[Log] Unsupported level '$level'", compact('message', 'context'));
            return;
        }

        $this->logger->{$level}($message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function exception(Throwable $exception): void
    {
        $context = [
            'exception' => $exception,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'type' => get_class($exception),
        ];

        $this->exceptionLogger->critical($exception->getMessage(), $context);
    }
}