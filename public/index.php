<?php

/**
 * ElliePHP Framework - Application Entry Point
 *
 * This file is the entry point for all HTTP requests.
 * The web server should be configured to route all requests here.
 */

use ElliePHP\Bootstrap\HttpApplication;


require __DIR__ . '/../vendor/autoload.php';

HttpApplication::init()
    ->boot();