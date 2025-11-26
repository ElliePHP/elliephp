# ElliePHP Console - Usage Guide

Super easy-to-use CLI built on Symfony Console.

## Quick Start

### 1. Basic Setup (No Container)

```php
#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use ElliePHP\Console\Application;
use App\Console\GreetCommand;

$app = new Application();
$app->add(new GreetCommand());
$app->run();
```

### 2. With Container (Optional)

```php
#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use ElliePHP\Console\Application;

$container = require __DIR__ . '/bootstrap/container.php';

$app = new Application($container, 'My App', '1.0.0');
$app->addCommands([
    GreetCommand::class, // Resolved from container
    AnotherCommand::class,
]);
$app->run();
```

## Creating Commands

### Simple Command

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

### Command with Options

```php
<?php

namespace App\Console;

use ElliePHP\Console\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ProcessCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('process')
             ->setDescription('Process items')
             ->addArgument('file', InputArgument::REQUIRED, 'File to process')
             ->addOption('verbose', 'v', InputOption::VALUE_NONE, 'Verbose output')
             ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit items', '10');
    }

    protected function handle(): int
    {
        $file = $this->argument('file');
        $verbose = $this->option('verbose');
        $limit = (int) $this->option('limit');

        if ($verbose) {
            $this->info("Processing: {$file}");
            $this->comment("Limit: {$limit}");
        }

        // Your logic here
        $this->success('Processing complete!');

        return self::SUCCESS;
    }
}
```

## Output Methods

### Basic Output

```php
protected function handle(): int
{
    $this->success('Operation successful!');
    $this->error('Something went wrong!');
    $this->info('Here is some information');
    $this->warning('This is a warning');
    $this->note('This is a note');
    $this->comment('This is a comment');
    
    return self::SUCCESS;
}
```

### Structured Output

```php
protected function handle(): int
{
    // Title
    $this->title('User Management');
    
    // Section
    $this->section('User List');
    
    // Table
    $this->table(
        ['ID', 'Name', 'Email'],
        [
            [1, 'John Doe', 'john@example.com'],
            [2, 'Jane Smith', 'jane@example.com'],
        ]
    );
    
    return self::SUCCESS;
}
```

### Interactive Commands

```php
protected function handle(): int
{
    // Ask a question
    $name = $this->ask('What is your name?', 'Guest');
    
    // Ask for confirmation
    if ($this->confirm('Do you want to continue?', true)) {
        $this->success('Continuing...');
    }
    
    // Ask for choice
    $action = $this->choice(
        'What would you like to do?',
        ['create', 'update', 'delete'],
        'create'
    );
    
    $this->info("Selected: {$action}");
    
    return self::SUCCESS;
}
```

### Raw Output

```php
protected function handle(): int
{
    // Write a line (with newline)
    $this->line('This is a line');
    
    // Write without newline
    $this->write('Loading...');
    $this->write(' Done!');
    
    return self::SUCCESS;
}
```

## Accessing Container

If you provided a container to the Application, you can access it in your commands:

```php
protected function handle(): int
{
    if ($this->container) {
        $service = $this->container->get('my.service');
        // Use the service
    }
    
    return self::SUCCESS;
}
```

## Complete Example

```php
<?php

namespace App\Console;

use ElliePHP\Console\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UserCreateCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('user:create')
             ->setDescription('Create a new user')
             ->addArgument('email', InputArgument::REQUIRED, 'User email')
             ->addOption('name', null, InputOption::VALUE_REQUIRED, 'User name')
             ->addOption('admin', 'a', InputOption::VALUE_NONE, 'Make user admin');
    }

    protected function handle(): int
    {
        $this->title('Create New User');
        
        $email = $this->argument('email');
        $name = $this->option('name') ?? $this->ask('What is the user\'s name?');
        $isAdmin = $this->option('admin');
        
        if (!$this->confirm("Create user {$name} ({$email})?", true)) {
            $this->warning('Cancelled');
            return self::FAILURE;
        }
        
        // Your user creation logic here
        // if ($this->container) {
        //     $userService = $this->container->get('user.service');
        //     $userService->create($email, $name, $isAdmin);
        // }
        
        $this->success("User {$name} created successfully!");
        
        $this->table(
            ['Field', 'Value'],
            [
                ['Email', $email],
                ['Name', $name],
                ['Admin', $isAdmin ? 'Yes' : 'No'],
            ]
        );
        
        return self::SUCCESS;
    }
}
```

## Command Exit Codes

Use these constants for return values:

- `self::SUCCESS` - Command executed successfully
- `self::FAILURE` - Command failed
- `self::INVALID` - Invalid command usage

## That's It!

The API is designed to be super simple. Just:
1. Extend `BaseCommand`
2. Implement `configure()` and `handle()`
3. Use the helper methods
4. Return `self::SUCCESS` or `self::FAILURE`

No need to deal with Symfony's Input/Output interfaces directly - we handle that for you!

