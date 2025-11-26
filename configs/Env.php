<?php

/**
 * Configuration for environment variables.
 *
 * This file defines the required environment variables for the application.
 */
return [
    'required_configs' => [

        'APP_NAME',

        'APP_TIMEZONE',

        'APP_ENV',
    ],


    'app_timezone' => env('APP_TIMEZONE', 'UTC'),
    'app_env' => env('APP_ENV', 'debug'),
    'app_name' => env('APP_NAME', 'ElliePHP')
];
