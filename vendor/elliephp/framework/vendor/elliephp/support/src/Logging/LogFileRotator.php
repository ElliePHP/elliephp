<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Logging;

use ElliePHP\Components\Support\Util\File;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Manages the cleanup of old log files.
 */
class LogFileRotator
{
    private const int SECONDS_PER_DAY = 86400;

    /**
     * @param string $storagePath The base path where logs are stored.
     * @param LoggerInterface $logger A logger to report the status of the cleanup operation.
     */
    public function __construct(
        private string $storagePath,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
        $this->storagePath = rtrim($storagePath, DIRECTORY_SEPARATOR);
    }

    /**
     * Clears log files older than a specified number of days from a given directory.
     *
     * @param string $subdirectory The subdirectory within the storage path to clean (e.g., 'Application').
     * @param string $filePattern The file pattern to match (e.g., 'RadioAPI*.log').
     * @param int $lifetimeDays The maximum age of log files in days.
     * @return int The number of files deleted.
     */
    public function clear(string $subdirectory, string $filePattern, int $lifetimeDays): int
    {
        if ($this->storagePath === '' || $lifetimeDays <= 0) {
            return 0;
        }

        try {
            $logDir = $this->storagePath . DIRECTORY_SEPARATOR . $subdirectory;

            if (!is_dir($logDir)) {
                return 0;
            }

            $files = File::glob($logDir . DIRECTORY_SEPARATOR . $filePattern);

            $cutoffTime = time() - (self::SECONDS_PER_DAY * $lifetimeDays);
            $deletedCount = 0;

            foreach ($files as $file) {
                if (File::isFile($file) && filemtime($file) < $cutoffTime) {
                    if (File::delete($file)) {
                        $deletedCount++;
                    } else {
                        $this->logger->warning('Failed to delete log file.', compact('file'));
                    }
                }
            }

            if ($deletedCount > 0) {
                $this->logger->info("Cleared $deletedCount old log files.", compact('subdirectory', 'filePattern'));
            }

            return $deletedCount;

        } catch (Throwable $e) {
            $this->logger->error('Failed to clear old logs.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 0;
        }
    }
}