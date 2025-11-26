<?php

namespace ElliePHP\Components\Support\Util;

final class Url
{
    /**
     * Parse a URL into its components.
     *
     * @param string $url The URL to parse.
     *
     * @return array|null The parsed components or null on failure.
     */
    public static function parse(string $url): ?array
    {
        $parts = parse_url($url);
        return $parts !== false ? $parts : null;
    }

    /**
     * Build a URL from components.
     *
     * @param array $parts The URL components.
     *
     * @return string The built URL.
     */
    public static function build(array $parts): string
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ':' . $parts['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Get the query string from a URL.
     *
     * @param string $url The URL.
     *
     * @return string|null The query string or null.
     */
    public static function query(string $url): ?string
    {
        $parts = self::parse($url);
        return $parts['query'] ?? null;
    }

    /**
     * Parse query string into an array.
     *
     * @param string $url The URL or query string.
     *
     * @return array The parsed query parameters.
     */
    public static function queryArray(string $url): array
    {
        $query = Str::contains($url, '?') ? self::query($url) : $url;

        if ($query === null) {
            return [];
        }

        parse_str($query, $result);
        return $result;
    }

    /**
     * Add query parameters to a URL.
     *
     * @param string $url The URL.
     * @param array $params The parameters to add.
     *
     * @return string The URL with added parameters.
     */
    public static function addQuery(string $url, array $params): string
    {
        if (empty($params)) {
            return $url;
        }

        $parts = self::parse($url);
        $existing = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $existing);
        }

        $merged = array_merge($existing, $params);
        $parts['query'] = http_build_query($merged);

        return self::build($parts);
    }

    /**
     * Remove query parameters from a URL.
     *
     * @param string $url The URL.
     * @param array|null $keys Specific keys to remove, or null to remove all.
     *
     * @return string The URL without query parameters.
     */
    public static function removeQuery(string $url, ?array $keys = null): string
    {
        $parts = self::parse($url);

        if (!isset($parts['query'])) {
            return $url;
        }

        if ($keys === null) {
            unset($parts['query']);
            return self::build($parts);
        }

        parse_str($parts['query'], $params);
        foreach ($keys as $key) {
            unset($params[$key]);
        }

        if (empty($params)) {
            unset($parts['query']);
        } else {
            $parts['query'] = http_build_query($params);
        }

        return self::build($parts);
    }

    /**
     * Check if a URL is valid.
     *
     * @param string $url The URL to validate.
     *
     * @return bool True if valid, false otherwise.
     */
    public static function isValid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if a URL uses HTTPS.
     *
     * @param string $url The URL.
     *
     * @return bool True if secure, false otherwise.
     */
    public static function isSecure(string $url): bool
    {
        $parts = self::parse($url);
        return isset($parts['scheme']) && $parts['scheme'] === 'https';
    }

    /**
     * Get the domain from a URL.
     *
     * @param string $url The URL.
     *
     * @return string|null The domain or null.
     */
    public static function domain(string $url): ?string
    {
        $parts = self::parse($url);
        return $parts['host'] ?? null;
    }

    /**
     * Get the path from a URL.
     *
     * @param string $url The URL.
     *
     * @return string|null The path or null.
     */
    public static function path(string $url): ?string
    {
        $parts = self::parse($url);
        return $parts['path'] ?? null;
    }

    /**
     * Get the scheme from a URL.
     *
     * @param string $url The URL.
     *
     * @return string|null The scheme or null.
     */
    public static function scheme(string $url): ?string
    {
        $parts = self::parse($url);
        return $parts['scheme'] ?? null;
    }

    /**
     * Ensure a URL has a protocol.
     *
     * @param string $url The URL.
     * @param string $protocol The default protocol.
     *
     * @return string The URL with protocol.
     */
    public static function ensureProtocol(string $url, string $protocol = 'https'): string
    {
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }

        return $protocol . '://' . ltrim($url, '/');
    }

    /**
     * Convert a URL to HTTPS.
     *
     * @param string $url The URL.
     *
     * @return string The HTTPS URL.
     */
    public static function toHttps(string $url): string
    {
        return preg_replace('/^http:/', 'https:', $url);
    }

    /**
     * Convert a URL to HTTP.
     *
     * @param string $url The URL.
     *
     * @return string The HTTP URL.
     */
    public static function toHttp(string $url): string
    {
        return preg_replace('/^https:/', 'http:', $url);
    }

    /**
     * Get the base URL (scheme + host + port).
     *
     * @param string $url The URL.
     *
     * @return string The base URL.
     */
    public static function base(string $url): string
    {
        $parts = self::parse($url);

        return self::build([
            'scheme' => $parts['scheme'] ?? 'http',
            'host' => $parts['host'] ?? '',
            'port' => $parts['port'] ?? null,
        ]);
    }

    /**
     * Join URL segments.
     *
     * @param string ...$segments The segments to join.
     *
     * @return string The joined URL.
     */
    public static function join(string ...$segments): string
    {
        if (empty($segments)) {
            return '';
        }

        $first = array_shift($segments);
        $result = rtrim($first, '/');

        foreach ($segments as $segment) {
            $result .= '/' . trim($segment, '/');
        }

        return $result;
    }

    /**
     * Encode URL components.
     *
     * @param string $value The value to encode.
     *
     * @return string The encoded value.
     */
    public static function encode(string $value): string
    {
        return urlencode($value);
    }

    /**
     * Decode URL components.
     *
     * @param string $value The value to decode.
     *
     * @return string The decoded value.
     */
    public static function decode(string $value): string
    {
        return urldecode($value);
    }

    /**
     * Get the root domain from a URL.
     *
     * @param string $url The URL.
     *
     * @return string|null The root domain or null.
     */
    public static function rootDomain(string $url): ?string
    {
        $domain = self::domain($url);

        if ($domain === null) {
            return null;
        }

        $parts = explode('.', $domain);

        if (count($parts) <= 2) {
            return $domain;
        }

        return implode('.', array_slice($parts, -2));
    }

    /**
     * Check if two URLs have the same domain.
     *
     * @param string $url1 First URL.
     * @param string $url2 Second URL.
     *
     * @return bool True if same domain, false otherwise.
     */
    public static function sameDomain(string $url1, string $url2): bool
    {
        return self::domain($url1) === self::domain($url2);
    }

    /**
     * Sanitize a URL.
     *
     * @param string $url The URL to sanitize.
     *
     * @return string|null The sanitized URL or null if invalid.
     */
    public static function sanitize(string $url): ?string
    {
        $sanitized = filter_var($url, FILTER_SANITIZE_URL);
        return $sanitized !== false ? $sanitized : null;
    }

    /**
     * Get the fragment (hash) from a URL.
     *
     * @param string $url The URL.
     *
     * @return string|null The fragment or null.
     */
    public static function fragment(string $url): ?string
    {
        $parts = self::parse($url);
        return $parts['fragment'] ?? null;
    }

    /**
     * Set or replace the fragment in a URL.
     *
     * @param string $url The URL.
     * @param string|null $fragment The fragment to set, or null to remove.
     *
     * @return string The URL with updated fragment.
     */
    public static function setFragment(string $url, ?string $fragment): string
    {
        $parts = self::parse($url);

        if ($fragment === null) {
            unset($parts['fragment']);
        } else {
            $parts['fragment'] = ltrim($fragment, '#');
        }

        return self::build($parts);
    }

    /**
     * Check if a URL is relative.
     *
     * @param string $url The URL.
     *
     * @return bool True if relative, false otherwise.
     */
    public static function isRelative(string $url): bool
    {
        return !preg_match('/^https?:\/\//', $url) && !preg_match('/^\/\//', $url);
    }

    /**
     * Check if a URL is absolute.
     *
     * @param string $url The URL.
     *
     * @return bool True if absolute, false otherwise.
     */
    public static function isAbsolute(string $url): bool
    {
        return !self::isRelative($url);
    }

    /**
     * Convert relative URL to absolute using a base URL.
     *
     * @param string $relativeUrl The relative URL.
     * @param string $baseUrl The base URL.
     *
     * @return string The absolute URL.
     */
    public static function toAbsolute(string $relativeUrl, string $baseUrl): string
    {
        if (self::isAbsolute($relativeUrl)) {
            return $relativeUrl;
        }

        $base = self::parse($baseUrl);

        if ($relativeUrl[0] === '/') {
            return self::build([
                'scheme' => $base['scheme'] ?? 'https',
                'host' => $base['host'] ?? '',
                'port' => $base['port'] ?? null,
                'path' => $relativeUrl,
            ]);
        }

        $basePath = $base['path'] ?? '/';
        $dir = dirname($basePath);

        return self::build([
            'scheme' => $base['scheme'] ?? 'https',
            'host' => $base['host'] ?? '',
            'port' => $base['port'] ?? null,
            'path' => rtrim($dir, '/') . '/' . ltrim($relativeUrl, '/'),
        ]);
    }

    /**
     * Get the file extension from a URL path.
     *
     * @param string $url The URL.
     *
     * @return string|null The extension or null.
     */
    public static function extension(string $url): ?string
    {
        $path = self::path($url);

        if ($path === null) {
            return null;
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return $ext !== '' ? $ext : null;
    }

    /**
     * Get the filename from a URL path.
     *
     * @param string $url The URL.
     * @param bool $withExtension Include extension or not.
     *
     * @return string|null The filename or null.
     */
    public static function filename(string $url, bool $withExtension = true): ?string
    {
        $path = self::path($url);

        if ($path === null) {
            return null;
        }

        $filename = pathinfo($path, PATHINFO_BASENAME);

        if (!$withExtension) {
            $filename = pathinfo($path, PATHINFO_FILENAME);
        }

        return $filename !== '' ? $filename : null;
    }

    /**
     * Strip protocol from URL.
     *
     * @param string $url The URL.
     *
     * @return string The URL without protocol.
     */
    public static function stripProtocol(string $url): string
    {
        return preg_replace('/^https?:\/\//', '', $url);
    }

    /**
     * Strip www from domain.
     *
     * @param string $url The URL.
     *
     * @return string The URL without www.
     */
    public static function stripWww(string $url): string
    {
        $parts = self::parse($url);

        if (isset($parts['host'])) {
            $parts['host'] = preg_replace('/^www\./', '', $parts['host']);
        }

        return self::build($parts);
    }

    /**
     * Normalize a URL (lowercase scheme/host, remove default ports, etc).
     *
     * @param string $url The URL.
     *
     * @return string The normalized URL.
     */
    public static function normalize(string $url): string
    {
        $parts = self::parse($url);

        if (!$parts) {
            return $url;
        }

        if (isset($parts['scheme'])) {
            $parts['scheme'] = strtolower($parts['scheme']);
        }

        if (isset($parts['host'])) {
            $parts['host'] = strtolower($parts['host']);
        }

        if (isset($parts['port'])) {
            $defaultPorts = ['http' => 80, 'https' => 443];
            $scheme = $parts['scheme'] ?? '';

            if (isset($defaultPorts[$scheme]) && $parts['port'] === $defaultPorts[$scheme]) {
                unset($parts['port']);
            }
        }

        if (isset($parts['path']) && $parts['path'] === '') {
            $parts['path'] = '/';
        }

        return self::build($parts);
    }

    /**
     * Check if URL has a specific query parameter.
     *
     * @param string $url The URL.
     * @param string $key The parameter key.
     *
     * @return bool True if parameter exists, false otherwise.
     */
    public static function hasQueryParam(string $url, string $key): bool
    {
        $params = self::queryArray($url);
        return array_key_exists($key, $params);
    }

    /**
     * Get a specific query parameter value.
     *
     * @param string $url The URL.
     * @param string $key The parameter key.
     * @param mixed $default Default value if not found.
     *
     * @return mixed The parameter value or default.
     */
    public static function getQueryParam(string $url, string $key, mixed $default = null): mixed
    {
        $params = self::queryArray($url);
        return $params[$key] ?? $default;
    }

    /**
     * Set a specific query parameter (replaces if exists).
     *
     * @param string $url The URL.
     * @param string $key The parameter key.
     * @param mixed $value The parameter value.
     *
     * @return string The URL with updated parameter.
     */
    public static function setQueryParam(string $url, string $key, mixed $value): string
    {
        return self::addQuery($url, [$key => $value]);
    }

    /**
     * Check if URL is a data URL.
     *
     * @param string $url The URL.
     *
     * @return bool True if data URL, false otherwise.
     */
    public static function isDataUrl(string $url): bool
    {
        return str_starts_with($url, 'data:');
    }

    /**
     * Check if URL is a mailto link.
     *
     * @param string $url The URL.
     *
     * @return bool True if mailto link, false otherwise.
     */
    public static function isMailto(string $url): bool
    {
        return Str::startsWith($url, 'mailto:');
    }

    /**
     * Check if URL is a tel link.
     *
     * @param string $url The URL.
     *
     * @return bool True if tel link, false otherwise.
     */
    public static function isTel(string $url): bool
    {
        return Str::startsWith($url, 'tel:');
    }

    /**
     * Get port number from URL (returns default port if not specified).
     *
     * @param string $url The URL.
     *
     * @return int|null The port number or null.
     */
    public static function port(string $url): ?int
    {
        $parts = self::parse($url);

        if (isset($parts['port'])) {
            return (int) $parts['port'];
        }

        $scheme = $parts['scheme'] ?? '';
        $defaultPorts = ['http' => 80, 'https' => 443, 'ftp' => 21];

        return $defaultPorts[$scheme] ?? null;
    }

    /**
     * Get subdomain from a URL.
     *
     * @param string $url The URL.
     *
     * @return string|null The subdomain or null.
     */
    public static function subdomain(string $url): ?string
    {
        $domain = self::domain($url);

        if ($domain === null) {
            return null;
        }

        $parts = explode('.', $domain);

        if (count($parts) <= 2) {
            return null;
        }

        array_splice($parts, -2);
        return implode('.', $parts);
    }

    /**
     * Replace path in URL.
     *
     * @param string $url The URL.
     * @param string $newPath The new path.
     *
     * @return string The URL with replaced path.
     */
    public static function replacePath(string $url, string $newPath): string
    {
        $parts = self::parse($url);
        $parts['path'] = $newPath;
        return self::build($parts);
    }

    /**
     * Replace domain in URL.
     *
     * @param string $url The URL.
     * @param string $newDomain The new domain.
     *
     * @return string The URL with replaced domain.
     */
    public static function replaceDomain(string $url, string $newDomain): string
    {
        $parts = self::parse($url);
        $parts['host'] = $newDomain;
        return self::build($parts);
    }

    /**
     * Get URL without query string and fragment.
     *
     * @param string $url The URL.
     *
     * @return string The clean URL.
     */
    public static function clean(string $url): string
    {
        $parts = self::parse($url);
        unset($parts['query'], $parts['fragment']);
        return self::build($parts);
    }

    /**
     * Compare two URLs for equality (ignoring query order and fragments).
     *
     * @param string $url1 First URL.
     * @param string $url2 Second URL.
     * @param bool $ignoreQuery Ignore query parameters in comparison.
     *
     * @return bool True if URLs are equal, false otherwise.
     */
    public static function equals(string $url1, string $url2, bool $ignoreQuery = false): bool
    {
        if (!$ignoreQuery) {
            $params1 = self::queryArray($url1);
            $params2 = self::queryArray($url2);

            ksort($params1);
            ksort($params2);

            if ($params1 !== $params2) {
                return false;
            }

        }
        $url1 = self::removeQuery($url1);
        $url2 = self::removeQuery($url2);

        return self::setFragment($url1, null) === self::setFragment($url2, null);
    }


    /**
     * Check if a URL is online.
     *
     * @param string $url
     * @param float $timeout Connection timeout in seconds (default: 0.5)
     * @return bool
     */
    public static function isOnline(string $url, float $timeout = 0.5): bool
    {
        if (!self::isValid($url)) {
            if (preg_match('/^\d{1,3}(\.\d{1,3}){3}:\d+$/', $url)) {
                $url = 'tcp://' . $url;
            } else {
                return false;
            }
        }

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];
        $port = $parsedUrl['port'] ?? (($parsedUrl['scheme'] ?? 'http') === 'https' ? 443 : 80);

        $fp = @fsockopen($host, $port, $errno, $errstr, 0);
        if ($fp) {
            stream_set_blocking($fp, false);
            fclose($fp);
            return true;
        }

        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($fp) {
            fclose($fp);
            return true;
        }

        $dnsRecords = array_merge(
            @dns_get_record($host, DNS_A) ?: [],
            @dns_get_record($host, DNS_AAAA) ?: []
        );

        foreach ($dnsRecords as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip) {
                $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
                if ($fp) {
                    fclose($fp);
                    return true;
                }
            }
        }

        return false;
    }
}



