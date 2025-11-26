<?php


use ElliePHP\Application\Console\Command\CacheClearCommand;
use ElliePHP\Application\Console\Command\MakeControllerCommand;
use ElliePHP\Application\Console\Command\RoutesCommand;
use ElliePHP\Application\Console\Command\ServeCommand;

return [
    'app' => [
        MakeControllerCommand::class,
        CacheClearCommand::class,
        ServeCommand::class,
        RoutesCommand::class
    ]
];
