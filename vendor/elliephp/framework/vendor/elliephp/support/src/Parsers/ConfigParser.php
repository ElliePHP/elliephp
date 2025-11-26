<?php

namespace ElliePHP\Components\Support\Parsers;

use RuntimeException;

/**
 * Configuration parser with dot notation support
 *
 * Loads and manages configuration files from the config directory.
 * Supports dot notation for nested array access (e.g., 'database.connections.mysql').
 */
class ConfigParser
{
    /**
     * Loaded configuration data
     *
     * @var array
     */
    protected array $config = [];

    /**
     * Configuration directory path
     *
     * @var string
     */
    protected string $configPath;

    /**
     * Create a new ConfigParser instance
     *
     * @param string $configPath
     */
    public function __construct(string $configPath)
    {
        $this->configPath = rtrim($configPath, '/');
    }

    /**
     * Load a configuration file
     *
     * @param string $name
     * @return void
     */
    public function load(string $name): void
    {
        $file = $this->configPath . '/' . $name . '.php';

        if (!file_exists($file)) {
            throw new RuntimeException("Configuration file [$name] not found.");
        }

        $this->config[$name] = require $file;
    }

    /**
     * Get a configuration value using dot notation
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $config = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($config) || !isset($config[$segment])) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    /**
     * Set a configuration value using dot notation
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $config = &$this->config;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $config[$segment] = $value;
            } else {
                if (!isset($config[$segment]) || !is_array($config[$segment])) {
                    $config[$segment] = [];
                }
                $config = &$config[$segment];
            }
        }
    }

    /**
     * Check if a configuration value exists
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $segments = explode('.', $key);
        $config = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($config) || !isset($config[$segment])) {
                return false;
            }
            $config = $config[$segment];
        }

        return true;
    }

    /**
     * Get all configuration data
     *
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Load all configuration files from the config directory
     *
     * @return void
     */
    public function loadAll(): void
    {
        $files = glob($this->configPath . '/*.php');

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $this->load($name);
        }
    }
}