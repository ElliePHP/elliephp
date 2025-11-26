<?php

namespace ElliePHP\Application\Controllers;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

final readonly class WelcomeController
{
    public function process(): ResponseInterface
    {
      return new JsonResponse
      ([
          'message' => 'Welcome to ElliePHP!',
          'version' => 1.0,
      ]);
    }


}