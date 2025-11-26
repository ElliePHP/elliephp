<?php


use ElliePHP\Application\Commands\MakeControllerCommand;
use ElliePHP\Application\Commands\RoutesCommand;
use ElliePHP\Application\Commands\ServeCommand;

return [
    'app' => [
        MakeControllerCommand::class,
        ServeCommand::class,
        RoutesCommand::class
    ]
];
