# Ellie PHP Cache Component

A lightweight, PSR-16 compliant caching library for PHP 8.4+ with support for multiple storage backends.

[![PHP Version](https://img.shields.io/badge/php-%5E8.4-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Features

- **PSR-16 Simple Cache** compliant
- **Multiple drivers**: Redis, Valkey, SQLite, File, APCu
- **Automatic key prefixing** for namespace isolation
- **Type-safe** with full PHP 8.4 type declarations
- **Zero configuration** defaults for quick setup
- **Garbage collection** for file-based caches
- **Batch operations** for improved performance

## Requirements

- PHP 8.4 or higher
- PDO extension (for SQLite driver)
- APCu extension (optional, for APCu driver)
- Redis/Valkey server (optional, for Redis driver)

## Installation

```bash
composer require elliephp/cache
```

## Quick Start

```php
use ElliePHP\Components\Cache\CacheFactory;
use ElliePHP\Components\Cache\Cache;

// Create a file-based cache
$driver = CacheFactory::createFileDriver([
    'path' => '/path/to/cache'
]);

$cache = new Cache($driver);

// Store a value
$cache->set('user:123', 'John Doe', 3600);

// Retrieve a value
$name = $cache->get('user:123'); // "John Doe"

// Check if exists
if ($cache->has('user:123')) {
    // ...
}

// Delete a value
$cache->delete('user:123');

// Clear all cache
$cache->clear();
```

## Drivers

### File Driver

Stores cache data as JSON files on the filesystem.

```php
$driver = CacheFactory::createFileDriver([
    'path' => '/path/to/cache',
    'create_directory' => true,      // Auto-create directory
    'directory_permissions' => 0755  // Directory permissions
]);

$cache = new Cache($driver);
```

**Garbage Collection:**
```php
// Clear expired cache files
$deletedCount = $driver->clearExpired();
```

### Redis Driver

Uses Redis or Valkey for high-performance caching.

```php
$driver = CacheFactory::createRedisDriver([
    'host' => '127.0.0.1',
    'port' => 6379,
    'password' => null,      // Optional
    'database' => 0,         // Redis database number
    'timeout' => 5.0,        // Connection timeout
    'prefix' => 'myapp:'     // Optional key prefix
]);

$cache = new Cache($driver);
```

### SQLite Driver

Stores cache in a SQLite database file.

```php
$driver = CacheFactory::createSQLiteDriver([
    'path' => '/path/to/cache.db',
    'create_directory' => true,
    'directory_permissions' => 0755
]);

$cache = new Cache($driver);
```

**Garbage Collection:**
```php
// Clear expired cache entries
$deletedCount = $driver->clearExpired();
```

### APCu Driver

Uses PHP's APCu extension for in-memory caching.

```php
$driver = CacheFactory::createApcuDriver();
$cache = new Cache($driver);
```

## Usage

### Basic Operations

```php
// Set with TTL (in seconds)
$cache->set('key', 'value', 3600);

// Set without expiry (forever)
$cache->set('key', 'value', null);
$cache->set('key', 'value', 0);

// Get with default value
$value = $cache->get('key', 'default');

// Check existence
if ($cache->has('key')) {
    // Key exists and is not expired
}

// Delete
$cache->delete('key');

// Clear all
$cache->clear();
```

### Batch Operations

```php
// Get multiple values
$values = $cache->getMultiple(['user:1', 'user:2', 'user:3'], 'default');
// Returns: ['user:1' => 'John', 'user:2' => 'Jane', 'user:3' => 'default']

// Set multiple values
$cache->setMultiple([
    'user:1' => 'John',
    'user:2' => 'Jane',
    'user:3' => 'Bob'
], 3600);

// Delete multiple values
$cache->deleteMultiple(['user:1', 'user:2', 'user:3']);
```

### TTL with DateInterval

```php
use DateInterval;

// Cache for 1 hour
$cache->set('key', 'value', new DateInterval('PT1H'));

// Cache for 1 day
$cache->set('key', 'value', new DateInterval('P1D'));

// Cache for 30 days
$cache->set('key', 'value', new DateInterval('P30D'));
```

### Cache Statistics

```php
// Get total number of cached items
$count = $cache->count();

// Get total cache size in bytes
$size = $cache->size();
```

## Key Prefixing

All cache keys are automatically prefixed with `ellie_cache:` to prevent collisions. You don't need to worry about this - it's handled transparently.

```php
$cache->set('user', 'John');
// Actual key stored: "ellie_cache:user"

$cache->get('user');
// Retrieves: "ellie_cache:user"
```

## Key Validation

Keys are validated according to PSR-16 specifications:

- Cannot be empty
- Maximum 255 characters
- Cannot contain: `{}()/\@:`

```php
try {
    $cache->set('invalid{key}', 'value');
} catch (\ElliePHP\Components\Cache\Exceptions\InvalidArgumentException $e) {
    // Handle invalid key
}
```

## Factory Method

Use the factory for dynamic driver selection:

```php
use ElliePHP\Components\Cache\CacheFactory;
use ElliePHP\Components\Cache\CacheDrivers;

$driver = CacheFactory::create(CacheDrivers::REDIS, [
    'host' => '127.0.0.1',
    'port' => 6379
]);

// Or use string
$driver = CacheFactory::create('redis', [
    'host' => '127.0.0.1'
]);
```

Available driver constants:
- `CacheDrivers::REDIS` - Redis driver
- `CacheDrivers::VALKEY` - Valkey (Redis-compatible)
- `CacheDrivers::SQLITE` - SQLite driver
- `CacheDrivers::FILE` - File driver
- `CacheDrivers::APCU` - APCu driver

## Error Handling

```php
use ElliePHP\Components\Cache\Exceptions\CacheException;
use ElliePHP\Components\Cache\Exceptions\InvalidArgumentException;

try {
    $driver = CacheFactory::createRedisDriver([
        'host' => 'invalid-host'
    ]);
} catch (CacheException $e) {
    // Handle connection errors
    echo "Cache error: " . $e->getMessage();
}

try {
    $cache->set('', 'value');
} catch (InvalidArgumentException $e) {
    // Handle invalid key
    echo "Invalid key: " . $e->getMessage();
}
```

## Best Practices

### 1. Use Appropriate Drivers

- **APCu**: Best for single-server setups, fastest performance
- **Redis/Valkey**: Best for distributed systems, shared cache
- **SQLite**: Good for moderate traffic, persistent cache
- **File**: Good for development, simple deployments

### 2. Set Reasonable TTLs

```php
// Short-lived data (5 minutes)
$cache->set('api:rate-limit', $data, 300);

// Medium-lived data (1 hour)
$cache->set('user:session', $data, 3600);

// Long-lived data (1 day)
$cache->set('config:settings', $data, 86400);

// Permanent data (until manually deleted)
$cache->set('app:version', $data, null);
```

### 3. Use Batch Operations

```php
// Instead of multiple get() calls
$user1 = $cache->get('user:1');
$user2 = $cache->get('user:2');
$user3 = $cache->get('user:3');

// Use getMultiple()
$users = $cache->getMultiple(['user:1', 'user:2', 'user:3']);
```

### 4. Handle Cache Misses

```php
$data = $cache->get('expensive:data');

if ($data === null) {
    // Cache miss - fetch from source
    $data = $this->fetchExpensiveData();
    
    // Store in cache
    $cache->set('expensive:data', $data, 3600);
}

return $data;
```

### 5. Regular Garbage Collection

For file and SQLite drivers, schedule periodic cleanup:

```php
// In a cron job or scheduled task
$driver->clearExpired();
```

## Performance Tips

1. **Use Redis for high-traffic applications** - It's the fastest for concurrent access
2. **Batch operations** - Use `getMultiple()` and `setMultiple()` when possible
3. **Set appropriate TTLs** - Don't cache forever unless necessary
4. **Monitor cache size** - Use `$cache->size()` to track growth
5. **Use key prefixes** - The built-in prefix prevents collisions

## Testing

```bash
# Run tests
composer test

# Run tests with coverage
composer test:coverage
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/elliephp/cache/issues)
- **Source**: [GitHub Repository](https://github.com/elliephp/cache)

## Credits

Created by [Joey Boli](mailto:bankuboy@proton.me)

## Changelog

See [IMPROVEMENTS.md](IMPROVEMENTS.md) for recent improvements and changes.
