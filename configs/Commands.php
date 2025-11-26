<?php


use ElliePHP\Application\Console\Command\MakeControllerCommand;
use ElliePHP\Application\Console\Command\RoutesCommand;
use ElliePHP\Application\Console\Command\ServeCommand;

return [
    'app' => [
        MakeControllerCommand::class,
        ServeCommand::class,
        RoutesCommand::class
    ]
];
