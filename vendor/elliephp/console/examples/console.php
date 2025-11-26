#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Example console entry point - super simple!
 */

require __DIR__ . '/../vendor/autoload.php';

use ElliePHP\Console\Application;
use Examples\GreetCommand;
use Examples\AdvancedCommand;

// Create the application
$app = new Application(null, 'Example Console', '1.0.0');

// Add commands - it's this easy!
$app->add(new GreetCommand());
$app->add(new AdvancedCommand());

// Or add multiple at once:
// $app->addCommands([
//     new GreetCommand(),
//     new AdvancedCommand(),
// ]);

// Run it
$app->run();

