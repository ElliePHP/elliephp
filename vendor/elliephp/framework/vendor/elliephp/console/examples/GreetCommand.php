<?php

declare(strict_types=1);

namespace Examples;

use ElliePHP\Console\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Example command demonstrating the easy-to-use API.
 */
class GreetCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('greet')
             ->setDescription('Greet someone')
             ->addArgument('name', InputArgument::OPTIONAL, 'Name to greet', 'World');
    }

    protected function handle(): int
    {
        $name = $this->argument('name');
        
        $this->success("Hello, {$name}!");
        $this->info('This is an info message');
        $this->note('This is a note');
        
        return self::SUCCESS;
    }
}

