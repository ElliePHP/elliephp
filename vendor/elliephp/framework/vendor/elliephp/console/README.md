# ElliePHP Console

A lightweight PSR-11 container-aware wrapper around Symfony Console.

## Installation

```bash
composer require elliephp/console
```

## Features

- Minimal wrapper around Symfony Console
- Optional PSR-11 container integration
- Framework agnostic
- No framework-specific commands

## Usage

### Basic Setup (No Container)

```php
#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use ElliePHP\Console\Application;

$app = new Application();
$app->add(new YourCommand());
$app->run();
```

### With Container (Optional)

```php
#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use ElliePHP\Console\Application;

$container = require __DIR__ . '/bootstrap/container.php';

$app = new Application($container, 'My App', '1.0.0');
$app->addCommands([
    YourCommand::class, // Resolved from container
]);
$app->run();
```

## Creating Commands

```php
<?php

namespace App\Console;

use ElliePHP\Console\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;

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
        return self::SUCCESS;
    }
}
```

## Registering Commands

```php
// Without container
$app = new Application();
$app->add(new GreetCommand());

// With container (optional)
$app = new Application($container);
$app->addCommands([
    new GreetCommand(),           // Direct instance
    AnotherCommand::class,        // Resolved from container if available
]);

$app->run();
```

## BaseCommand Helpers

### Output Methods

```php
// Basic output
$this->success('Success message');
$this->error('Error message');
$this->info('Info message');
$this->warning('Warning message');
$this->note('Note message');
$this->comment('Comment message');

// Structured output
$this->title('Section Title');
$this->section('Subsection');
$this->table(['Header1', 'Header2'], [['Row1', 'Row2']]);

// Interactive
$name = $this->ask('What is your name?', 'Guest');
$confirm = $this->confirm('Continue?', true);
$choice = $this->choice('Select option', ['a', 'b', 'c'], 'a');

// Raw output
$this->line('A line of text');
$this->write('Text without newline');
```

### Accessing Input

```php
// Get arguments
$name = $this->argument('name');

// Get options
$verbose = $this->option('verbose');
```

### Container Access

```php
// Access container (only if provided to Application)
if ($this->container) {
    $service = $this->container->get('service');
}
```

### Exit Codes

```php
return self::SUCCESS;  // Command succeeded
return self::FAILURE;  // Command failed
return self::INVALID;  // Invalid usage
```

## Quick Reference

### Minimal Command

```php
class MyCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('my:command')
             ->setDescription('Does something');
    }

    protected function handle(): int
    {
        $this->success('Done!');
        return self::SUCCESS;
    }
}
```

### Common Patterns

```php
// Get input
$name = $this->argument('name');
$verbose = $this->option('verbose');

// Output
$this->success('Success!');
$this->error('Error!');
$this->info('Info');
$this->table(['Col1', 'Col2'], $rows);

// Interactive
$answer = $this->ask('Question?', 'default');
$confirm = $this->confirm('Continue?', true);
```

## More Examples

See [USAGE.md](USAGE.md) for comprehensive examples including:
- Commands with arguments and options
- Interactive commands
- Table output
- Container integration
- Complete real-world examples

## Requirements

- PHP 8.1+
- symfony/console ^6.0|^7.0
- psr/container ^2.0

## License

MIT
