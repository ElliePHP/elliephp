<?php

namespace ElliePHP\Application\Commands;

use ElliePHP\Console\Command\BaseCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class ServeCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('serve')
            ->setDescription('Start the development server')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Server host', '127.0.0.1')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Server port', 8000)
            ->addOption('docroot', 'd', InputOption::VALUE_OPTIONAL, 'Document root', 'public');
    }

    protected function handle(): int
    {
        $host = $this->option('host');
        $port = $this->option('port');
        $docroot = $this->option('docroot');

        $this->info("Starting development server on http://$host:$port");
        $this->info("Document root: $docroot");
        $this->warning('Press Ctrl+C to stop the server');

        $process = new Process([
            'php',
            '-S',
            "$host:$port",
            '-t',
            $docroot,
        ]);

        // Dev server runs indefinitely
        $process->setTimeout(null);

        // Stream output to the console
        $process->run(function ($type, $buffer): void {
            echo $buffer;
        });

        if (!$process->isSuccessful()) {
            $this->error('Failed to start the PHP development server.');
            $this->error($process->getErrorOutput());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
