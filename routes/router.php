<?php

use ElliePHP\Application\Controllers\WelcomeController;
use ElliePHP\Components\Routing\Router;

Router::get('/', WelcomeController::class);