<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Http;

use DateTimeInterface;
use ElliePHP\Components\Support\Util\Json;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

final class Response
{
    private Psr17Factory $factory;

    public function __construct(
        private readonly ResponseInterface $response
    ) {
        $this->factory = new Psr17Factory();
    }

    /**
     * Create a new response.
     *
     * @param mixed $content Content (string, array, object).
     * @param int $status HTTP status code.
     * @param array $headers Headers.
     *
     * @return ResponseInterface
     */
    public function make(
        mixed $content = '',
        int $status = 200,
        array $headers = []
    ): ResponseInterface
    {
        // Auto-detect content type
        if (is_array($content) || is_object($content)) {
            return $this->json($content, $status, $headers);
        }

        $response = $this->factory->createResponse($status);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        if ($content !== null && $content !== '') {
            $stream = $this->factory->createStream((string) $content);
            $response = $response->withBody($stream);
        }

        return $response;
    }

    /**
     * Create a JSON response.
     *
     * @param mixed $data Data to encode.
     * @param int $status HTTP status code.
     * @param array $headers Additional headers.
     * @param int $flags JSON encoding flags.
     *
     * @return ResponseInterface
     */
    public function json(
        mixed $data,
        int $status = 200,
        array $headers = [],
        int $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
    ): ResponseInterface
    {
        $json = json_encode($data, $flags);

        $response = $this->factory->createResponse($status);

        foreach (array_merge(['Content-Type' => 'application/json'], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStream($json);
        return $response->withBody($stream);
    }

    /**
     * Create a JSONP response.
     *
     * @param string $callback Callback function name.
     * @param mixed $data Data to encode.
     * @param int $status HTTP status code.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function jsonp(
        string $callback,
        mixed $data,
        int $status = 200,
        array $headers = []
    ): ResponseInterface
    {
        $json = Json::encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $content = sprintf('/**/ typeof %s === \'function\' && %s(%s);', $callback, $callback, $json);

        $response = $this->factory->createResponse($status);

        foreach (array_merge(['Content-Type' => 'text/javascript'], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStream($content);
        return $response->withBody($stream);
    }

    /**
     * Create an HTML response.
     *
     * @param string $html HTML content.
     * @param int $status HTTP status code.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function html(
        string $html,
        int $status = 200,
        array $headers = []
    ): ResponseInterface
    {
        $response = $this->factory->createResponse($status);

        foreach (array_merge(['Content-Type' => 'text/html; charset=utf-8'], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStream($html);
        return $response->withBody($stream);
    }

    /**
     * Create a plain text response.
     *
     * @param string $text Text content.
     * @param int $status HTTP status code.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function text(
        string $text,
        int $status = 200,
        array $headers = []
    ): ResponseInterface
    {
        $response = $this->factory->createResponse($status);

        foreach (array_merge(['Content-Type' => 'text/plain; charset=utf-8'], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStream($text);
        return $response->withBody($stream);
    }

    /**
     * Create an XML response.
     *
     * @param string $xml XML content.
     * @param int $status HTTP status code.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function xml(
        string $xml,
        int $status = 200,
        array $headers = []
    ): ResponseInterface
    {
        $response = $this->factory->createResponse($status);

        foreach (array_merge(['Content-Type' => 'application/xml; charset=utf-8'], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStream($xml);
        $response = $response->withBody($stream);

        return $response;
    }

    /**
     * Create a redirect response.
     *
     * @param string $url Redirect URL.
     * @param int $status HTTP status code (301, 302, 303, 307, 308).
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function redirect(
        string $url,
        int $status = 302,
        array $headers = []
    ): ResponseInterface
    {
        $response = $this->factory->createResponse($status);

        foreach (array_merge(['Location' => $url], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /**
     * Create a permanent redirect (301).
     *
     * @param string $url Redirect URL.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function redirectPermanent(string $url, array $headers = []): ResponseInterface
    {
        return $this->redirect($url, 301, $headers);
    }

    /**
     * Create a temporary redirect (302).
     *
     * @param string $url Redirect URL.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function redirectTemporary(string $url, array $headers = []): ResponseInterface
    {
        return $this->redirect($url, 302, $headers);
    }

    /**
     * Create a "see other" redirect (303).
     *
     * @param string $url Redirect URL.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function redirectSeeOther(string $url, array $headers = []): ResponseInterface
    {
        return $this->redirect($url, 303, $headers);
    }

    /**
     * Create a redirect to previous URL.
     *
     * @param string|null $fallback Fallback URL.
     * @param int $status Status code.
     *
     * @return ResponseInterface
     */
    public function back(?string $fallback = '/', int $status = 302): ResponseInterface
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
        return $this->redirect($referer, $status);
    }

    /**
     * Create an empty response.
     *
     * @param int $status HTTP status code.
     *
     * @return ResponseInterface
     */
    public function noContent(int $status = 204): ResponseInterface
    {
        return $this->factory->createResponse($status);
    }

    /**
     * Alias for noContent().
     *
     * @param int $status HTTP status code.
     *
     * @return ResponseInterface
     */
    public function empty(int $status = 204): ResponseInterface
    {
        return $this->noContent($status);
    }

    /**
     * Create a download response.
     *
     * @param string $content File content.
     * @param string $filename Download filename.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function download(
        string $content,
        string $filename,
        array $headers = []
    ): ResponseInterface
    {
        $response = $this->factory->createResponse(200);

        foreach (array_merge([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => strlen($content),
        ], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStream($content);
        return $response->withBody($stream);
    }

    /**
     * Create a file download response.
     *
     * @param string $path File path.
     * @param string|null $filename Download filename.
     * @param array $headers Additional headers.
     *
     * @return ResponseInterface
     */
    public function file(
        string $path,
        ?string $filename = null,
        array $headers = []
    ): ResponseInterface
    {
        if (!file_exists($path)) {
            return $this->notFound('File not found');
        }

        $content = file_get_contents($path);
        $filename = $filename ?? basename($path);

        return $this->download($content, $filename, $headers);
    }

    /**
     * Create a streamed download response.
     *
     * @param string $path File path.
     * @param string|null $filename Download filename.
     * @param array $headers Additional headers.
     * @param bool $deleteAfter Delete file after download.
     *
     * @return ResponseInterface
     */
    public function streamDownload(
        string $path,
        ?string $filename = null,
        array $headers = [],
        bool $deleteAfter = false
    ): ResponseInterface
    {
        if (!file_exists($path)) {
            return $this->notFound('File not found');
        }

        $filename = $filename ?? basename($path);
        $resource = fopen($path, 'rb');

        $response = $this->factory->createResponse(200);

        foreach (array_merge([
            'Content-Type' => mime_content_type($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => filesize($path),
        ], $headers) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $stream = $this->factory->createStreamFromResource($resource);
        $response = $response->withBody($stream);

        if ($deleteAfter) {
            register_shutdown_function(static function() use ($path) {
                @unlink($path);
            });
        }

        return $response;
    }

    /**
     * Create a 200 OK response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function ok(mixed $content = '', array $headers = []): ResponseInterface
    {
        return $this->make($content, 200, $headers);
    }

    /**
     * Create a 201 Created response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function created(mixed $content = '', array $headers = []): ResponseInterface
    {
        return $this->make($content, 201, $headers);
    }

    /**
     * Create a 202 Accepted response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function accepted(mixed $content = '', array $headers = []): ResponseInterface
    {
        return $this->make($content, 202, $headers);
    }

    /**
     * Create a 400 Bad Request response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function badRequest(mixed $content = 'Bad Request', array $headers = []): ResponseInterface
    {
        return $this->make($content, 400, $headers);
    }

    /**
     * Create a 401 Unauthorized response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function unauthorized(mixed $content = 'Unauthorized', array $headers = []): ResponseInterface
    {
        return $this->make($content, 401, $headers);
    }

    /**
     * Create a 403 Forbidden response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function forbidden(mixed $content = 'Forbidden', array $headers = []): ResponseInterface
    {
        return $this->make($content, 403, $headers);
    }

    /**
     * Create a 404 Not Found response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function notFound(mixed $content = 'Not Found', array $headers = []): ResponseInterface
    {
        return $this->make($content, 404, $headers);
    }

    /**
     * Create a 405 Method Not Allowed response.
     *
     * @param array $allowed Allowed methods.
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function methodNotAllowed(
        array $allowed = [],
        mixed $content = 'Method Not Allowed',
        array $headers = []
    ): ResponseInterface
    {
        if (!empty($allowed)) {
            $headers['Allow'] = implode(', ', $allowed);
        }

        return $this->make($content, 405, $headers);
    }

    /**
     * Create a 409 Conflict response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function conflict(mixed $content = 'Conflict', array $headers = []): ResponseInterface
    {
        return $this->make($content, 409, $headers);
    }

    /**
     * Create a 422 Unprocessable Entity response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function unprocessable(mixed $content = 'Unprocessable Entity', array $headers = []): ResponseInterface
    {
        return $this->make($content, 422, $headers);
    }

    /**
     * Create a 429 Too Many Requests response.
     *
     * @param int|null $retryAfter Retry after seconds.
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function tooManyRequests(
        ?int $retryAfter = null,
        mixed $content = 'Too Many Requests',
        array $headers = []
    ): ResponseInterface
    {
        if ($retryAfter !== null) {
            $headers['Retry-After'] = $retryAfter;
        }

        return $this->make($content, 429, $headers);
    }

    /**
     * Create a 500 Internal Server Error response.
     *
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function serverError(mixed $content = 'Internal Server Error', array $headers = []): ResponseInterface
    {
        return $this->make($content, 500, $headers);
    }

    /**
     * Create a 503 Service Unavailable response.
     *
     * @param int|null $retryAfter Retry after seconds.
     * @param mixed $content
     * @param array $headers
     *
     * @return ResponseInterface
     */
    public function serviceUnavailable(
        ?int $retryAfter = null,
        mixed $content = 'Service Unavailable',
        array $headers = []
    ): ResponseInterface
    {
        if ($retryAfter !== null) {
            $headers['Retry-After'] = $retryAfter;
        }

        return $this->make($content, 503, $headers);
    }

    /**
     * Get the underlying PSR-7 response.
     *
     * @return ResponseInterface
     */
    public function psr(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Alias for psr().
     *
     * @return ResponseInterface
     */
    public function raw(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get response status code.
     *
     * @return int
     */
    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Alias for status().
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->status();
    }

    /**
     * Check if response is successful (2xx).
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Alias for isSuccessful().
     *
     * @return bool
     */
    public function successful(): bool
    {
        return $this->isSuccessful();
    }

    /**
     * Check if response is OK (200).
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->status() === 200;
    }

    /**
     * Check if response is a redirect (3xx).
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * Check if response is a client error (4xx).
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * Check if response is a server error (5xx).
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->status() >= 500 && $this->status() < 600;
    }

    /**
     * Check if response is forbidden (403).
     *
     * @return bool
     */
    public function isForbidden(): bool
    {
        return $this->status() === 403;
    }

    /**
     * Check if response is not found (404).
     *
     * @return bool
     */
    public function isNotFound(): bool
    {
        return $this->status() === 404;
    }

    /**
     * Get response body as string.
     *
     * @return string
     */
    public function body(): string
    {
        return (string) $this->response->getBody();
    }

    /**
     * Alias for body().
     *
     * @return string
     */
    public function content(): string
    {
        return $this->body();
    }

    /**
     * Get response body as JSON.
     *
     * @return string
     */
    public function toJson(): string
    {
        return Json::encode($this->body());
    }

    /**
     * Get response headers.
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * Get a header value.
     *
     * @param string $name
     * @param string|null $default
     *
     * @return string|null
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        $values = $this->response->getHeader($name);
        return $values[0] ?? $default;
    }

    /**
     * Check if response has header.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    /**
     * Add a header to response.
     *
     * @param string $name Header name.
     * @param string|array $value Header value.
     *
     * @return ResponseInterface
     */
    public function withHeader(string $name, string|array $value): ResponseInterface
    {
        return $this->response->withHeader($name, $value);
    }

    /**
     * Alias for withHeader().
     *
     * @param string $name
     * @param string|array $value
     *
     * @return ResponseInterface
     */
    public function header(string $name, string|array $value): ResponseInterface
    {
        return $this->withHeader($name, $value);
    }

    /**
     * Add multiple headers to response.
     *
     * @param array $headers Headers array.
     *
     * @return ResponseInterface
     */
    public function withHeaders(array $headers): ResponseInterface
    {
        $response = $this->response;
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        return $response;
    }

    /**
     * Set response status.
     *
     * @param int $status Status code.
     * @param string $reason Reason phrase.
     *
     * @return ResponseInterface
     */
    public function withStatus(int $status, string $reason = ''): ResponseInterface
    {
        return $this->response->withStatus($status, $reason);
    }

    /**
     * Alias for withStatus().
     *
     * @param int $status
     * @param string $reason
     *
     * @return ResponseInterface
     */
    public function setStatusCode(int $status, string $reason = ''): ResponseInterface
    {
        return $this->withStatus($status, $reason);
    }

    /**
     * Set response body.
     *
     * @param string $body Body content.
     *
     * @return ResponseInterface
     */
    public function withBody(string $body): ResponseInterface
    {
        $stream = $this->factory->createStream($body);
        return $this->response->withBody($stream);
    }

    /**
     * Alias for withBody().
     *
     * @param string $content
     *
     * @return ResponseInterface
     */
    public function setContent(string $content): ResponseInterface
    {
        return $this->withBody($content);
    }

    /**
     * Set cookie header.
     *
     * @param string $name Cookie name.
     * @param string $value Cookie value.
     * @param int $expires Expiration time.
     * @param string $path Cookie path.
     * @param string $domain Cookie domain.
     * @param bool $secure Secure flag.
     * @param bool $httpOnly HTTP only flag.
     * @param string $sameSite SameSite attribute.
     *
     * @return ResponseInterface
     */
    public function withCookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): ResponseInterface
    {
        $cookie = urlencode($name) . '=' . urlencode($value);

        if ($expires > 0) {
            $cookie .= '; Expires=' . gmdate('D, d M Y H:i:s T', $expires);
            $cookie .= '; Max-Age=' . ($expires - time());
        }

        if ($path) {
            $cookie .= '; Path=' . $path;
        }

        if ($domain) {
            $cookie .= '; Domain=' . $domain;
        }

        if ($secure) {
            $cookie .= '; Secure';
        }

        if ($httpOnly) {
            $cookie .= '; HttpOnly';
        }

        if ($sameSite) {
            $cookie .= '; SameSite=' . $sameSite;
        }

        return $this->response->withAddedHeader('Set-Cookie', $cookie);
    }

    /**
     * Alias for withCookie().
     *
     * @param string $name
     * @param string $value
     * @param int $minutes Expiration in minutes.
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param string $sameSite
     *
     * @return ResponseInterface
     */
    public function cookie(
        string $name,
        string $value,
        int $minutes = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): ResponseInterface
    {
        $expires = $minutes > 0 ? time() + ($minutes * 60) : 0;
        return $this->withCookie($name, $value, $expires, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    /**
     * Set cookie that expires when browser closes.
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param string $sameSite
     *
     * @return ResponseInterface
     */
    public function withoutCookie(
        string $name,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): ResponseInterface
    {
        return $this->withCookie($name, '', time() - 3600, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    /**
     * Set content type header.
     *
     * @param string $contentType
     *
     * @return ResponseInterface
     */
    public function contentType(string $contentType): ResponseInterface
    {
        return $this->withHeader('Content-Type', $contentType);
    }

    /**
     * Set cache control header.
     *
     * @param string $value
     *
     * @return ResponseInterface
     */
    public function cacheControl(string $value): ResponseInterface
    {
        return $this->withHeader('Cache-Control', $value);
    }

    /**
     * Set response to not be cached.
     *
     * @return ResponseInterface
     */
    public function noCache(): ResponseInterface
    {
        return $this->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Set ETag header.
     *
     * @param string $etag
     *
     * @return ResponseInterface
     */
    public function etag(string $etag): ResponseInterface
    {
        return $this->withHeader('ETag', $etag);
    }

    /**
     * Set Last-Modified header.
     *
     * @param int|DateTimeInterface $time
     *
     * @return ResponseInterface
     */
    public function lastModified(int|DateTimeInterface $time): ResponseInterface
    {
        if ($time instanceof DateTimeInterface) {
            $time = $time->getTimestamp();
        }

        return $this->withHeader('Last-Modified', gmdate('D, d M Y H:i:s T', $time));
    }

    /**
     * Send response to client.
     *
     * @return void
     */
    public function send(): void
    {
        // Send status line
        header(sprintf(
            'HTTP/%s %s %s',
            $this->response->getProtocolVersion(),
            $this->response->getStatusCode(),
            $this->response->getReasonPhrase()
        ), true, $this->response->getStatusCode());

        // Send headers
        foreach ($this->response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        // Send body
        echo $this->response->getBody();
    }

    /**
     * Send response and exit.
     *
     * @return never
     */
    public function sendAndExit(): never
    {
        $this->send();
        exit;
    }
}