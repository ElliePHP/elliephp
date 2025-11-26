<?php

declare(strict_types=1);

namespace ElliePHP\Console;

use ElliePHP\Console\Command\BaseCommand as EllieBaseCommand;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;

/**
 * A lightweight wrapper around Symfony Console with optional container support.
 */
class Application extends SymfonyApplication
{
    protected ?ContainerInterface $container = null;

    /**
     * @param ContainerInterface|null $container Optional PSR-11 container
     * @param string $name Application name
     * @param string $version Application version
     */
    public function __construct(
        ?ContainerInterface $container = null,
        string $name = 'ElliePHP Console',
        string $version = '1.0.0'
    ) {
        parent::__construct($name, $version);
        $this->container = $container;
    }

    /**
     * Add a single command instance.
     */
    public function add(Command $command): ?Command
    {
        // Inject container if command is BaseCommand
        if ($command instanceof EllieBaseCommand && $this->container) {
            $command->setContainer($this->container);
        }

        return parent::add($command);
    }

    /**
     * Add multiple commands at once.
     *
     * Accepts:
     * - Command instances
     * - Class names (resolved from container if available)
     *
     * @param array<int, Command|string> $commands
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function addCommands(array $commands): void
    {
        foreach ($commands as $command) {
            if (is_string($command)) {
                // Try to resolve from container first
                if ($this->container && $this->container->has($command)) {
                    $command = $this->container->get($command);
                } else {
                    // Fallback to instantiation
                    $command = new $command();
                }
            }

            if ($command instanceof Command) {
                $this->add($command);
            }
        }
    }

    /**
     * Get the container instance.
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }
}

