# ElliePHP Framework

A fast, modular PHP microframework focused on clean architecture, performance, and zero-bloat components.

## Requirements

- PHP 8.4 or higher
- Composer
- PDO extension

## Installation

```bash
composer install
cp .env.example .env
```

## Quick Start

### 1. Configure Environment

Edit `.env` file with your settings:

```env
APP_NAME='ElliePHP'
APP_DEBUG=true
APP_TIMEZONE='UTC'
CACHE_DRIVER=file
```

### 2. Define Routes

Routes are defined in `routes/router.php`:

```php
use ElliePHP\Application\Controllers\WelcomeController;use ElliePHP\Components\Routing\Router;

Router::get('/', WelcomeController::class);
Router::post('/api/users', [UserController::class, 'create']);
```

### 3. Create Controllers

Controllers live in `app/Controllers/`:

```php
namespace ElliePHP\Application\Controllers;

use Psr\Http\Message\ResponseInterface;

final readonly class WelcomeController
{
    public function process(): ResponseInterface
    {
        return response()->json([
            'message' => 'Hello World'
        ]);
    }
}
```

### 4. Add Middleware

Register global middleware in `app/Configs/Middleware.php`:

```php
return [
    'global_middlewares' => [
        LoggingMiddleware::class,
        CorsMiddleware::class,
    ],
];
```

## Dependency Injection

ElliePHP uses PHP-DI for automatic dependency injection. Controllers and services get their dependencies automatically:

```php
final readonly class UserController
{
    public function __construct(
        private UserService $userService
    ) {
    }

    public function index(): ResponseInterface
    {
        $users = $this->userService->getAllUsers();
        return response()->json(['users' => $users]);
    }
}
```

Configure bindings in `configs/Container.php`. See `docs/DEPENDENCY_INJECTION.md` for details.

## Helper Functions

### Container
```php
container(); // Get container instance
container(UserService::class); // Resolve service
```

### Environment Variables
```php
env('APP_NAME'); // Get environment variable
env('APP_DEBUG', false); // With default value
```

### Configuration
```php
config('middleware.global_middlewares'); // Get config value
config(['app.name' => 'MyApp']); // Set config values
```

### HTTP
```php
request(); // Get current request
response(200)->json(['data' => $data]); // Create JSON response
```

### Caching
```php
cache()->set('key', 'value', 3600); // Cache for 1 hour
cache()->get('key', 'default'); // Get cached value
cache('redis')->set('key', 'value'); // Use specific driver
```

### Logging
```php
report()->info('User logged in', ['user_id' => 123]);
report()->error('Something went wrong');
report()->exception($exception);
```

### Path Helpers
```php
root_path('config/app.php');
app_path('Controllers/UserController.php');
storage_path('logs/app.log');
routes_path('api.php');
```

## Cache Drivers

Supported cache drivers:
- `file` - File-based caching (default)
- `redis` - Redis caching
- `sqlite` - SQLite database caching
- `apcu` - APCu memory caching

Configure in `.env`:
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Directory Structure

```
├── app/
│   ├── Configs/         # Configuration files
│   ├── Controllers/     # HTTP controllers
│   ├── Middlewares/     # Custom middleware
│   └── Services/        # Business logic services
├── public/
│   └── index.php        # Application entry point
├── routes/
│   └── router.php       # Route definitions
├── src/
│   ├── Kernel/          # Framework core
│   └── Support/         # Helper functions
├── storage/
│   ├── Cache/           # Cache files
│   └── Logs/            # Application logs
└── tests/               # Unit tests
```

## Development

Run the built-in PHP server:

```bash
php -S localhost:8000 -t public
```

Visit: http://localhost:8000

## Testing

```bash
composer test
composer test:coverage
```

## License

MIT License

## Support

- Issues: https://github.com/elliephp/framework/issues
- Documentation: https://github.com/elliephp
