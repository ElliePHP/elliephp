<?php

namespace ElliePHP\Application\Controllers;

use ElliePHP\Bootstrap\HttpApplication;
use Psr\Http\Message\ResponseInterface;

final readonly class WelcomeController
{
    public function process(): ResponseInterface
    {
        return response()->json(
            [
                'message' => 'Welcome to ElliePHP!',
                'version' => HttpApplication::VERSION,
            ]
        );
    }


}