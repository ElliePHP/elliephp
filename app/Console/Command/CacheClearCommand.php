<?php

namespace ElliePHP\Application\Console\Command;

use ElliePHP\Console\Command\BaseCommand;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Input\InputOption;


class CacheClearCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clear application caches')
            ->addOption('config', null, InputOption::VALUE_NONE, 'Clear config cache')
            ->addOption('routes', null, InputOption::VALUE_NONE, 'Clear routes cache')
            ->addOption('views', null, InputOption::VALUE_NONE, 'Clear views cache')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Clear all caches');
    }

    protected function handle(): int
    {
        $this->title('Clearing Application Cache');

        $clearAll = $this->option('all');
        $cleared = [];

        if ($clearAll || $this->option('config')) {
            $this->clearCache('config', 'Config');
            $cleared[] = 'config';
        }

        if ($clearAll || $this->option('routes')) {
            $this->clearCache('routes', 'Routes');
            $cleared[] = 'routes';
        }

        if ($clearAll || $this->option('views')) {
            $this->clearCache('views', 'Views');
            $cleared[] = 'views';
        }

        if (empty($cleared)) {
            $this->clearCache('Cache', 'All');
            $cleared[] = 'all';
        }

        $this->success('Cache cleared successfully: ' . implode(', ', $cleared));

        return self::SUCCESS;
    }

    private function clearCache(string $path, string $label): void
    {
        $cachePath = storage_cache_path($path);

        if (!is_dir($cachePath)) {
            $this->comment("Skipping $label: Directory not found");
            return;
        }

        $count = $this->deleteFiles($cachePath);
        $this->info("✓ {$label}: {$count} files removed");
    }

    private function deleteFiles(string $directory): int
    {
        $count = 0;

        if (!is_dir($directory)) {
            return $count;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() !== '.gitignore') {
                unlink($file->getRealPath());
                $count++;
            }
        }

        return $count;
    }
}