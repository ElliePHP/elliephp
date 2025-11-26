<?php

namespace ElliePHP\Application\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Random\RandomException;

final class ApplicationLoggingMiddleware implements MiddlewareInterface
{
    /**
     * @throws RandomException
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $correlationId = $request->getHeaderLine('X-Correlation-ID')
            ?: bin2hex(random_bytes(8));

        $request = $request->withAttribute('correlation_id', $correlationId);

        $requestSize = $request->getBody()->getSize() ?? 0;

        $response = $handler->handle($request);

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);
        $memoryDelta = memory_get_usage(true) - $startMemory;
        $responseSize = $response->getBody()->getSize();

        $routeName = $request->getAttribute('route_name')
            ?? $request->getAttribute('route')
            ?? null;

        report()->info('HTTP Request Analytics', [
            'correlation_id'      => $correlationId,
            'method'              => $request->getMethod(),
            'uri'                 => (string) $request->getUri(),
            'status'              => $response->getStatusCode(),
            'route'               => $routeName,
            'duration_ms'         => $durationMs,
            'memory_delta_bytes'  => $memoryDelta,
            'request_size_bytes'  => $requestSize,
            'response_size_bytes' => $responseSize,
            'client_ip'           => $request->getServerParams()['REMOTE_ADDR'] ?? null,
            'user_agent'          => $request->getHeaderLine('User-Agent'),
        ]);

        return $response->withHeader('X-Correlation-ID', $correlationId);
    }
}
