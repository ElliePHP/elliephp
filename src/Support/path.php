<?php

if (!function_exists("root_path")) {
    /**
     * Get the root path of the application
     *
     * @param string $path Optional path to append
     * @return string Full path to root directory
     */
    function root_path(string $path = ""): string
    {
        if (defined('ELLIE_BASE_PATH')) {
            $base = ELLIE_BASE_PATH;
        } else {
            $base = dirname(__DIR__, 2);
        }
        
        return $path !== "" && $path !== "0"
            ? $base . "/" . ltrim($path, "/")
            : $base;
    }
}

if (!function_exists("src_path")) {
    /**
     * Get the framework source path
     *
     * @param string $path Optional path to append
     * @return string Full path to src directory
     */
    function src_path(string $path = ""): string
    {
        return root_path(
            "src" .
            ($path !== "" && $path !== "0" ? "/" . ltrim($path, "/") : ""),
        );
    }
}


if (!function_exists("app_path")) {
    /**
     * Get the application path
     *
     * @param string $path Optional path to append
     * @return string Full path to app directory
     */
    function app_path(string $path = ""): string
    {
        return root_path(
            "app" .
            ($path !== "" && $path !== "0" ? "/" . ltrim($path, "/") : ""),
        );
    }
}


if (!function_exists("routes_path")) {
    /**
     * Get the routes path
     *
     * @param string $path Optional path to append
     * @return string Full path to routes directory
     */
    function routes_path(string $path = ""): string
    {
        return root_path(
            "routes" .
            ($path !== "" && $path !== "0" ? "/" . ltrim($path, "/") : ""),
        );
    }
}


if (!function_exists("storage_path")) {
    /**
     * Get the storage path
     *
     * @param string $path Optional path to append
     * @return string Full path to storage directory
     */
    function storage_path(string $path = ""): string
    {
        return root_path(
            "storage" .
            ($path !== "" && $path !== "0" ? "/" . ltrim($path, "/") : ""),
        );
    }
}

if (!function_exists("storage_logs_path")) {
    /**
     * Get the storage logs path
     *
     * @param string $path Optional path to append
     * @return string Full path to storage/Logs directory
     */
    function storage_logs_path(string $path = ""): string
    {
        return storage_path(
            "Logs" .
            ($path !== "" && $path !== "0" ? "/" . ltrim($path, "/") : ""),
        );
    }
}

if (!function_exists("storage_cache_path")) {
    /**
     * Get the storage cache path
     *
     * @param string $path Optional path to append
     * @return string Full path to storage/Cache directory
     */
    function storage_cache_path(string $path = ""): string
    {
        return storage_path(
            "Cache" .
            ($path !== "" && $path !== "0" ? "/" . ltrim($path, "/") : ""),
        );
    }
}