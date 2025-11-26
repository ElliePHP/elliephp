<?php

namespace ElliePHP\Application\Console\Command;

use Closure;
use ElliePHP\Components\Routing\Router;
use ElliePHP\Components\Support\Util\File;
use ElliePHP\Console\Command\BaseCommand;

class RoutesCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('routes')
            ->setDescription('Display registered routes');
    }

    protected function handle(): int
    {
        $this->title('Application Routes');

        // Load routes file if it exists
        $routesFile = root_path('routes/router.php');
        if (File::exists($routesFile)) {
            require $routesFile;
        }

        $routes = $this->extractRoutes();

        if (empty($routes)) {
            $this->info('No routes registered.');
            return self::SUCCESS;
        }

        $this->table(
            ['Method', 'URI', 'Handler', 'Name'],
            $routes
        );

        $this->success(sprintf('Total routes: %d', count($routes)));

        return self::SUCCESS;
    }

    private function extractRoutes(): array
    {
        $routes = [];
        $routeCollection = Router::getRoutes();

        foreach ($routeCollection as $route) {
            $routes[] = [
                $route['method'] ?? 'GET',
                $route['path'] ?? '/',
                $this->formatHandler($route),
                $route['name'] ?? '-',
            ];
        }

        return $routes;
    }

    private function formatHandler(array $route): string
    {
        // If it's a class-based route
        if (!empty($route['class'])) {
            $class = $route['class'];
            $handler = $route['handler'];

            // Extract short class name
            $shortClass = substr($class, strrpos($class, '\\') + 1);

            return sprintf('%s@%s', $shortClass, $handler);
        }

        // If it's a closure
        if ($route['handler'] instanceof Closure) {
            return 'Closure';
        }

        // If handler is a string
        if (is_string($route['handler'])) {
            return $route['handler'];
        }

        return 'Unknown';
    }
}