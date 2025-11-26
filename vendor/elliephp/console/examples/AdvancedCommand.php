<?php

declare(strict_types=1);

namespace Examples;

use ElliePHP\Console\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Advanced example showing more features.
 */
class AdvancedCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('advanced')
             ->setDescription('Demonstrate advanced features')
             ->addArgument('action', InputArgument::REQUIRED, 'Action to perform')
             ->addOption('verbose', 'v', InputOption::VALUE_NONE, 'Verbose output')
             ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of times', '1');
    }

    protected function handle(): int
    {
        $action = $this->argument('action');
        $verbose = $this->option('verbose');
        $count = (int) $this->option('count');

        $this->title("Advanced Command Demo");
        
        $this->info("Action: {$action}");
        $this->info("Count: {$count}");
        
        if ($verbose) {
            $this->comment("Verbose mode enabled");
        }

        // Example table
        $this->table(
            ['Key', 'Value'],
            [
                ['Action', $action],
                ['Count', (string) $count],
                ['Verbose', $verbose ? 'Yes' : 'No'],
            ]
        );

        // Example interaction
        if ($this->confirm('Do you want to continue?', true)) {
            $this->success('Continuing...');
        } else {
            $this->warning('Cancelled by user');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

