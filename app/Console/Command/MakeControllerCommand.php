<?php

namespace ElliePHP\Application\Console\Command;

use ElliePHP\Components\Support\Util\File;
use ElliePHP\Components\Support\Util\Str;
use ElliePHP\Console\Command\BaseCommand;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class MakeControllerCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('make:controller')
            ->setDescription('Generate a new controller class')
            ->addArgument('name', InputArgument::REQUIRED, 'Controller name (e.g., UserController)')
            ->addOption('resource', 'r', InputOption::VALUE_NONE, 'Generate a resource controller')
            ->addOption('api', null, InputOption::VALUE_NONE, 'Generate an API controller');
    }

    protected function handle(): int
    {
        $name = $this->argument('name');
        $isResource = $this->option('resource');
        $isApi = $this->option('api');

        $className = $this->formatClassName($name);
        $path = $this->getFilePath($className);

        if (File::exists($path)) {
            $this->error("Controller already exists: $path");
            return self::FAILURE;
        }

        $this->ensureDirectoryExists(dirname($path));

        $content = $this->generateContent($className, $isResource, $isApi);
        file_put_contents($path, $content);

        $this->success("Controller created successfully!");
        $this->info("Location: $path");

        return self::SUCCESS;
    }

    private function formatClassName(string $name): string
    {
        $name = str_replace(['/', '\\'], '', $name);

        if (!Str::endsWith($name, 'Controller')) {
            $name .= 'Controller';
        }

        return $name;
    }

    private function getFilePath(string $className): string
    {
        return app_path("Http/Controllers/$className.php");
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!File::isDirectory($directory) && !FIle::makeDirectory($directory) && !File::isDirectory($directory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }
    }

    private function generateContent(string $className, bool $isResource, bool $isApi): string
    {
        $methods = $isResource ? $this->getResourceMethods($isApi) : $this->getBasicMethod();

        return <<<PHP
<?php

namespace ElliePHP\Application\Http\Controllers;

use Psr\Http\Message\ResponseInterface;

final class {$className}
{
{$methods}
}

PHP;
    }

    private function getBasicMethod(): string
    {
        return <<<'PHP'
    public function process(): ResponseInterface
    {
        return response()->json([
            'message' => 'Controller action'
        ]);
    }
PHP;
    }

    private function getResourceMethods(bool $isApi): string
    {
        if ($isApi) {
            return <<<'PHP'
    public function process(): ResponseInterface
    {
        return response()->json(['data' => []]);
    }

    public function show(int $id): ResponseInterface
    {
        return response()->json(['data' => []]);
    }

    public function store(): ResponseInterface
    {
        return response()->json(['data' => []], 201);
    }

    public function update(int $id): ResponseInterface
    {
        return response()->json(['data' => []]);
    }

    public function destroy(int $id): ResponseInterface
    {
        return response()->json([], 204);
    }
PHP;
        }

        return <<<'PHP'
    public function process(): ResponseInterface
    {
        return response()->json(['data' => []]);
    }

    public function create(): ResponseInterface
    {
        return response()->json(['form' => []]);
    }

    public function store(): ResponseInterface
    {
        return response()->json(['data' => []], 201);
    }

    public function show(int $id): ResponseInterface
    {
        return response()->json(['data' => []]);
    }

    public function edit(int $id): ResponseInterface
    {
        return response()->json(['form' => []]);
    }

    public function update(int $id): ResponseInterface
    {
        return response()->json(['data' => []]);
    }

    public function destroy(int $id): ResponseInterface
    {
        return response()->json([], 204);
    }
PHP;
    }
}