<?php

declare(strict_types=1);

namespace ElliePHP\Components\Support\Http;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use ElliePHP\Components\Support\Util\Json;
use ElliePHP\Components\Support\Util\Str;
use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;

final class Request
{
    private static ?ServerRequestCreator $creator = null;

    public function __construct(
        private readonly ServerRequestInterface $request
    )
    {
    }

    /**
     * Create a server request from globals.
     *
     * @return self
     */
    public static function fromGlobals(): self
    {
        if (self::$creator === null) {
            $factory = new Psr17Factory();
            self::$creator = new ServerRequestCreator(
                $factory,
                $factory,
                $factory,
                $factory
            );
        }

        return new self(self::$creator->fromGlobals());
    }

    /**
     * Create a new request.
     *
     * @param string $method HTTP method.
     * @param string $uri URI.
     * @param array $headers Headers.
     * @param string|resource|null $body Body.
     * @param string $version Protocol version.
     *
     * @return self
     */
    public static function create(
        string $method,
        string $uri,
        array  $headers = [],
        mixed  $body = null,
        string $version = '1.1'
    ): self
    {
        $factory = new Psr17Factory();
        $request = $factory->createRequest($method, $uri);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $stream = is_resource($body)
                ? $factory->createStreamFromResource($body)
                : $factory->createStream($body);
            $request = $request->withBody($stream);
        }

        return new self($request->withProtocolVersion($version));
    }

    /**
     * Get the underlying PSR-7 request.
     *
     * @return ServerRequestInterface
     */
    public function psr(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Alias for psr().
     *
     * @return ServerRequestInterface
     */
    public function raw(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Get request method.
     *
     * @return string
     */
    public function method(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Get request URI.
     *
     * @return string
     */
    public function uri(): string
    {
        return (string)$this->request->getUri();
    }

    /**
     * Alias for uri().
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri();
    }

    /**
     * Alias for uri().
     *
     * @return string
     */
    public function url(): string
    {
        return $this->uri();
    }

    /**
     * Get the full URL with query string.
     *
     * @return string
     */
    public function fullUrl(): string
    {
        $uri = $this->request->getUri();
        return (string)$uri;

    }

    /**
     * Get request path.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->request->getUri()->getPath();
    }

    /**
     * Get the URL without query parameters.
     *
     * @return string
     */
    public function urlWithoutQuery(): string
    {
        $uri = $this->request->getUri();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $port = $uri->getPort();
        $path = $uri->getPath();

        $url = $scheme . '://' . $host;

        if ($port && (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443))) {
            $url .= ':' . $port;
        }

        return $url . $path;
    }

    /**
     * Get a query parameter.
     *
     * @param string|null $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->request->getQueryParams();
        }

        return $this->request->getQueryParams()[$key] ?? $default;
    }

    /**
     * Get all query parameters.
     *
     * @return array
     */
    public function allQuery(): array
    {
        return $this->request->getQueryParams();
    }

    /**
     * Get a POST parameter.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function post(string $key, mixed $default = null): mixed
    {
        $body = $this->request->getParsedBody();
        return is_array($body) ? ($body[$key] ?? $default) : $default;
    }

    /**
     * Get all POST parameters.
     *
     * @return array
     */
    public function allPost(): array
    {
        return $this->request->getParsedBody() ?? [];
    }

    /**
     * Get input from query or post.
     *
     * @param string|array|null $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function input(string|array|null $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->all();
        }

        if (is_array($key)) {
            return $this->only($key);
        }

        return $this->query($key) ?? $this->post($key, $default);
    }

    /**
     * Get all input data.
     *
     * @return array
     */
    public function all(): array
    {
        return array_merge($this->allQuery(), $this->allPost());
    }

    /**
     * Get only specified keys from input.
     *
     * @param array $keys
     *
     * @return array
     */
    public function only(array $keys): array
    {
        $input = $this->all();
        return array_intersect_key($input, array_flip($keys));
    }

    /**
     * Get all input except specified keys.
     *
     * @param array $keys
     *
     * @return array
     */
    public function except(array $keys): array
    {
        $input = $this->all();
        return array_diff_key($input, array_flip($keys));
    }

    /**
     * Check if input has a key.
     *
     * @param string|array $key
     *
     * @return bool
     */
    public function has(string|array $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        $input = $this->all();

        return array_all($keys, static fn($k) => array_key_exists($k, $input));

    }

    /**
     * Check if input has any of the given keys.
     *
     * @param string|array $keys
     *
     * @return bool
     */
    public function hasAny(string|array $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $input = $this->all();

        return array_any($keys, static fn($key) => array_key_exists($key, $input));

    }

    /**
     * Check if input key exists and is not empty.
     *
     * @param string|array $key
     *
     * @return bool
     */
    public function filled(string|array $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $k) {
            $value = $this->input($k);
            if (empty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if input is missing a key.
     *
     * @param string|array $key
     *
     * @return bool
     */
    public function missing(string|array $key): bool
    {
        return !$this->has($key);
    }

    /**
     * Get input when filled, otherwise return default.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function whenFilled(string $key, mixed $default = null): mixed
    {
        if ($this->filled($key)) {
            return $this->input($key);
        }

        return $default;
    }

    /**
     * Get parsed body.
     *
     * @return array|object|null
     */
    public function body(): array|null|object
    {
        return $this->request->getParsedBody();
    }

    /**
     * Get input as boolean.
     *
     * @param string $key
     * @param bool $default
     *
     * @return bool
     */
    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->input($key, $default);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Alias for bool().
     *
     * @param string $key
     * @param bool $default
     *
     * @return bool
     */
    public function boolean(string $key, bool $default = false): bool
    {
        return $this->bool($key, $default);
    }

    /**
     * Get input as integer.
     *
     * @param string $key
     * @param int $default
     *
     * @return int
     */
    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Alias for int().
     *
     * @param string $key
     * @param int $default
     *
     * @return int
     */
    public function integer(string $key, int $default = 0): int
    {
        return $this->int($key, $default);
    }

    /**
     * Get input as float.
     *
     * @param string $key
     * @param float $default
     *
     * @return float
     */
    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->input($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Get input as string.
     *
     * @param string $key
     * @param string $default
     *
     * @return string
     */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key);
        return $value !== null ? (string)$value : $default;
    }

    /**
     * Get input as array.
     *
     * @param string $key
     * @param array $default
     *
     * @return array
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->input($key);
        return is_array($value) ? $value : $default;
    }

    /**
     * Get input as date.
     *
     * @param string $key
     * @param string|null $format
     * @param DateTimeZone|null $timezone
     *
     * @return DateTimeInterface|null
     */
    public function date(string $key, ?string $format = null, ?DateTimeZone $timezone = null): ?DateTimeInterface
    {
        $value = $this->input($key);

        if ($value === null) {
            return null;
        }

        if ($format) {
            $date = DateTimeImmutable::createFromFormat($format, $value, $timezone);
            return $date !== false ? $date : null;
        }

        try {
            return new DateTimeImmutable($value, $timezone);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Get body as JSON.
     *
     * @param bool $assoc
     *
     * @return mixed
     */
    public function json(bool $assoc = true): mixed
    {
        $body = (string)$this->request->getBody();
        return Json::decode($body, $assoc);
    }

    /**
     * Get a header value.
     *
     * @param string|null $name
     * @param string|null $default
     *
     * @return string|array|null
     */
    public function header(?string $name = null, ?string $default = null): string|array|null
    {
        if ($name === null) {
            return $this->headers();
        }

        $values = $this->request->getHeader($name);
        return $values[0] ?? $default;
    }

    /**
     * Check if request has header.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return $this->request->hasHeader($name);
    }

    /**
     * Get all headers.
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->request->getHeaders();
    }

    /**
     * Get bearer token from header.
     *
     * @return string|null
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');

        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    /**
     * Check if request is JSON.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type', '');
        return Str::contains($contentType, 'application/json');
    }

    /**
     * Check if request expects JSON.
     *
     * @return bool
     */
    public function expectsJson(): bool
    {
        return $this->isJson() || $this->wantsJson();
    }

    /**
     * Check if request wants JSON response.
     *
     * @return bool
     */
    public function wantsJson(): bool
    {
        $acceptable = $this->header('Accept', '');
        return Str::contains($acceptable, 'application/json');
    }

    /**
     * Check if request is AJAX.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Alias for isAjax().
     *
     * @return bool
     */
    public function ajax(): bool
    {
        return $this->isAjax();
    }

    /**
     * Check if request method matches.
     *
     * @param string $method
     *
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return strcasecmp($this->request->getMethod(), $method) === 0;
    }

    /**
     * Check if request is GET.
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Check if request is POST.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Check if request is PUT.
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    /**
     * Check if request is PATCH.
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Check if request is DELETE.
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Check if request is secure (HTTPS).
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->request->getUri()->getScheme() === 'https';
    }

    /**
     * Get the scheme.
     *
     * @return string
     */
    public function scheme(): string
    {
        return $this->request->getUri()->getScheme();
    }

    /**
     * Get the host.
     *
     * @return string
     */
    public function host(): string
    {
        return $this->request->getUri()->getHost();
    }

    /**
     * Get the port.
     *
     * @return int|null
     */
    public function port(): ?int
    {
        return $this->request->getUri()->getPort();
    }

    /**
     * Get uploaded files.
     *
     * @return array
     */
    public function files(): array
    {
        return $this->request->getUploadedFiles();
    }

    /**
     * Alias for files().
     *
     * @return array
     */
    public function allFiles(): array
    {
        return $this->files();
    }

    /**
     * Get a single uploaded file.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function file(string $key): mixed
    {
        return $this->request->getUploadedFiles()[$key] ?? null;
    }

    /**
     * Check if request has file.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->request->getUploadedFiles()[$key]);
    }

    /**
     * Get cookies.
     *
     * @return array
     */
    public function cookies(): array
    {
        return $this->request->getCookieParams();
    }

    /**
     * Get a cookie value.
     *
     * @param string|null $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function cookie(?string $name = null, mixed $default = null): mixed
    {
        if ($name === null) {
            return $this->cookies();
        }

        return $this->request->getCookieParams()[$name] ?? $default;
    }

    /**
     * Get server parameters.
     *
     * @return array
     */
    public function server(): array
    {
        return $this->request->getServerParams();
    }

    /**
     * Get client IP address.
     *
     * @return string|null
     */
    public function ip(): ?string
    {
        $server = $this->request->getServerParams();

        return $server['HTTP_X_FORWARDED_FOR']
            ?? $server['HTTP_CLIENT_IP']
            ?? $server['REMOTE_ADDR']
            ?? null;
    }

    /**
     * Get all client IPs.
     *
     * @return array
     */
    public function ips(): array
    {
        $server = $this->request->getServerParams();

        if (isset($server['HTTP_X_FORWARDED_FOR'])) {
            return array_map('trim', explode(',', $server['HTTP_X_FORWARDED_FOR']));
        }

        $ip = $this->ip();
        return $ip ? [$ip] : [];
    }

    /**
     * Get user agent.
     *
     * @return string|null
     */
    public function userAgent(): ?string
    {
        return $this->header('User-Agent');
    }

    /**
     * Get the referer.
     *
     * @return string|null
     */
    public function referer(): ?string
    {
        return $this->header('Referer');
    }

    /**
     * Alias for referer().
     *
     * @return string|null
     */
    public function referrer(): ?string
    {
        return $this->referer();
    }

    /**
     * Get request attributes.
     *
     * @return array
     */
    public function attributes(): array
    {
        return $this->request->getAttributes();
    }

    /**
     * Get a request attribute.
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function attribute(string $name, mixed $default = null): mixed
    {
        return $this->request->getAttribute($name, $default);
    }

    /**
     * Alias for attribute().
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->attribute($name, $default);
    }

    /**
     * Set a request attribute.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return self
     */
    public function withAttribute(string $name, mixed $value): self
    {
        return new self($this->request->withAttribute($name, $value));
    }

    /**
     * Alias for withAttribute().
     *
     * @param string $name
     * @param mixed $value
     *
     * @return self
     */
    public function set(string $name, mixed $value): self
    {
        return $this->withAttribute($name, $value);
    }

    /**
     * Merge attributes into the request.
     *
     * @param array $attributes
     *
     * @return self
     */
    public function merge(array $attributes): self
    {
        $request = $this;

        foreach ($attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        return $request;
    }
}