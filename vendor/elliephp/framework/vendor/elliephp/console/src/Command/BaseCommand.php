<?php

declare(strict_types=1);

namespace ElliePHP\Console\Command;

use Psr\Container\ContainerInterface as PsrContainerInterface;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Base command with helpful output methods and container access.
 */
abstract class BaseCommand extends Command
{
    // Command exit codes for convenience (inherited from parent)

    protected ?PsrContainerInterface $container = null;
    protected ?SymfonyStyle $io = null;
    protected ?InputInterface $input = null;
    protected ?OutputInterface $output = null;

    /**
     * Set the container instance.
     */
    public function setContainer(PsrContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Get the container instance.
     */
    public function getContainer(): ?PsrContainerInterface
    {
        return $this->container;
    }

    /**
     * Execute the command.
     */
    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        return $this->handle();
    }

    /**
     * Handle the command logic.
     * Override this method instead of execute().
     */
    abstract protected function handle(): int;

    /**
     * Display a success message.
     */
    protected function success(string $message): void
    {
        $this->io->success($message);
    }

    /**
     * Display an error message.
     */
    protected function error(string $message): void
    {
        $this->io->error($message);
    }

    /**
     * Display an info message.
     */
    protected function info(string $message): void
    {
        $this->io->info($message);
    }

    /**
     * Display a warning message.
     */
    protected function warning(string $message): void
    {
        $this->io->warning($message);
    }

    /**
     * Display a note.
     */
    protected function note(string $message): void
    {
        $this->io->note($message);
    }

    /**
     * Display a comment.
     */
    protected function comment(string $message): void
    {
        $this->io->comment($message);
    }

    /**
     * Display a title.
     */
    protected function title(string $title): void
    {
        $this->io->title($title);
    }

    /**
     * Display a section.
     */
    protected function section(string $section): void
    {
        $this->io->section($section);
    }

    /**
     * Display a table.
     * 
     * @param array<string> $headers
     * @param array<int, array<string>> $rows
     */
    protected function table(array $headers, array $rows): void
    {
        $this->io->table($headers, $rows);
    }

    /**
     * Ask a question.
     */
    protected function ask(string $question, ?string $default = null): string
    {
        return $this->io->ask($question, $default);
    }

    /**
     * Ask for confirmation.
     */
    protected function confirm(string $question, bool $default = true): bool
    {
        return $this->io->confirm($question, $default);
    }

    /**
     * Ask for a choice.
     * 
     * @param array<int|string, string> $choices
     */
    protected function choice(string $question, array $choices, mixed $default = null): mixed
    {
        return $this->io->choice($question, $choices, $default);
    }

    /**
     * Get an argument value.
     */
    protected function argument(string $name): mixed
    {
        return $this->input->getArgument($name);
    }

    /**
     * Get an option value.
     */
    protected function option(string $name): mixed
    {
        return $this->input->getOption($name);
    }

    /**
     * Write a line.
     */
    protected function line(string $message): void
    {
        $this->output->writeln($message);
    }

    /**
     * Write text without newline.
     */
    protected function write(string $message): void
    {
        $this->output->write($message);
    }
}
