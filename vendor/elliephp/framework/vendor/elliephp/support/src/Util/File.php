<?php

namespace ElliePHP\Components\Support\Util;

use RuntimeException;

final class File
{
    /**
     * Check if a file exists.
     *
     * @param string $path The file path.
     *
     * @return bool True if exists, false otherwise.
     */
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Check if a path is a file.
     *
     * @param string $path The path.
     *
     * @return bool True if file, false otherwise.
     */
    public static function isFile(string $path): bool
    {
        return is_file($path);
    }

    /**
     * Check if a path is a directory.
     *
     * @param string $path The path.
     *
     * @return bool True if directory, false otherwise.
     */
    public static function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Get the contents of a file.
     *
     * @param string $path The file path.
     *
     * @return string The file contents.
     * @throws RuntimeException If file cannot be read.
     */
    public static function get(string $path): string
    {
        if (!self::exists($path)) {
            throw new RuntimeException("File not found: $path");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Unable to read file: $path");
        }

        return $contents;
    }

    /**
     * Write contents to a file.
     *
     * @param string $path The file path.
     * @param string $contents The contents to write.
     * @param bool $lock Whether to acquire an exclusive lock.
     *
     * @return int The number of bytes written.
     * @throws RuntimeException If file cannot be written.
     */
    public static function put(string $path, string $contents, bool $lock = false): int
    {
        $flags = $lock ? LOCK_EX : 0;
        $result = file_put_contents($path, $contents, $flags);

        if ($result === false) {
            throw new RuntimeException("Unable to write file: $path");
        }

        return $result;
    }

    /**
     * Append contents to a file.
     *
     * @param string $path The file path.
     * @param string $contents The contents to append.
     *
     * @return int The number of bytes written.
     * @throws RuntimeException If file cannot be written.
     */
    public static function append(string $path, string $contents): int
    {
        $result = file_put_contents($path, $contents, FILE_APPEND | LOCK_EX);

        if ($result === false) {
            throw new RuntimeException("Unable to append to file: $path");
        }

        return $result;
    }

    /**
     * Prepend contents to a file.
     *
     * @param string $path The file path.
     * @param string $contents The contents to prepend.
     *
     * @return int The number of bytes written.
     * @throws RuntimeException If file cannot be written.
     */
    public static function prepend(string $path, string $contents): int
    {
        if (self::exists($path)) {
            $contents .= self::get($path);
        }

        return self::put($path, $contents);
    }

    /**
     * Delete a file.
     *
     * @param string $path The file path.
     *
     * @return bool True on success.
     */
    public static function delete(string $path): bool
    {
        if (!self::exists($path)) {
            return true;
        }

        return @unlink($path);
    }

    /**
     * Copy a file to a new location.
     *
     * @param string $source The source path.
     * @param string $destination The destination path.
     *
     * @return bool True on success.
     * @throws RuntimeException If file cannot be copied.
     */
    public static function copy(string $source, string $destination): bool
    {
        if (!self::exists($source)) {
            throw new RuntimeException("Source file not found: $source");
        }

        $result = copy($source, $destination);

        if (!$result) {
            throw new RuntimeException("Unable to copy file from $source to $destination");
        }

        return true;
    }

    /**
     * Move a file to a new location.
     *
     * @param string $source The source path.
     * @param string $destination The destination path.
     *
     * @return bool True on success.
     * @throws RuntimeException If file cannot be moved.
     */
    public static function move(string $source, string $destination): bool
    {
        if (!self::exists($source)) {
            throw new RuntimeException("Source file not found: $source");
        }

        $result = rename($source, $destination);

        if (!$result) {
            throw new RuntimeException("Unable to move file from $source to $destination");
        }

        return true;
    }

    /**
     * Get the file extension.
     *
     * @param string $path The file path.
     *
     * @return string The extension.
     */
    public static function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the file name without extension.
     *
     * @param string $path The file path.
     *
     * @return string The file name.
     */
    public static function name(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Get the file basename (name with extension).
     *
     * @param string $path The file path.
     *
     * @return string The basename.
     */
    public static function basename(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Get the directory name of a path.
     *
     * @param string $path The file path.
     *
     * @return string The directory name.
     */
    public static function dirname(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Get the file size in bytes.
     *
     * @param string $path The file path.
     *
     * @return int The file size.
     * @throws RuntimeException If file size cannot be determined.
     */
    public static function size(string $path): int
    {
        if (!self::exists($path)) {
            throw new RuntimeException("File not found: $path");
        }

        $size = filesize($path);
        if ($size === false) {
            throw new RuntimeException("Unable to get file size: $path");
        }

        return $size;
    }

    /**
     * Get the file's last modification time.
     *
     * @param string $path The file path.
     *
     * @return int The Unix timestamp.
     * @throws RuntimeException If modification time cannot be determined.
     */
    public static function lastModified(string $path): int
    {
        if (!self::exists($path)) {
            throw new RuntimeException("File not found: $path");
        }

        $time = filemtime($path);
        if ($time === false) {
            throw new RuntimeException("Unable to get modification time: $path");
        }

        return $time;
    }

    /**
     * Get the MIME type of file.
     *
     * @param string $path The file path.
     *
     * @return string|null The MIME type or null if cannot be determined.
     */
    public static function mimeType(string $path): ?string
    {
        if (!self::exists($path)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime !== false ? $mime : null;
    }

    /**
     * Check if a file is readable.
     *
     * @param string $path The file path.
     *
     * @return bool True if readable, false otherwise.
     */
    public static function isReadable(string $path): bool
    {
        return is_readable($path);
    }

    /**
     * Check if a file is writable.
     *
     * @param string $path The file path.
     *
     * @return bool True if writable, false otherwise.
     */
    public static function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    /**
     * Get the file permissions.
     *
     * @param string $path The file path.
     *
     * @return int The permissions.
     * @throws RuntimeException If permissions cannot be determined.
     */
    public static function permissions(string $path): int
    {
        if (!self::exists($path)) {
            throw new RuntimeException("File not found: $path");
        }

        $perms = fileperms($path);
        if ($perms === false) {
            throw new RuntimeException("Unable to get permissions: $path");
        }

        return $perms;
    }

    /**
     * Set the file permissions.
     *
     * @param string $path The file path.
     * @param int $mode The permissions' mode.
     *
     * @return bool True on success.
     * @throws RuntimeException If permissions cannot be set.
     */
    public static function chmod(string $path, int $mode): bool
    {
        if (!self::exists($path)) {
            throw new RuntimeException("File not found: $path");
        }

        $result = chmod($path, $mode);
        if (!$result) {
            throw new RuntimeException("Unable to set permissions: $path");
        }

        return true;
    }

    /**
     * Get all files in a directory.
     *
     * @param string $directory The directory path.
     * @param bool $recursive Whether to search recursively.
     *
     * @return array The file paths.
     */
    public static function files(string $directory, bool $recursive = false): array
    {
        if (!self::isDirectory($directory)) {
            return [];
        }

        $pattern = $recursive ? '/**/*' : '/*';
        $files = glob($directory . $pattern, GLOB_BRACE);

        return array_filter($files ?: [], static fn($file) => self::isFile($file));
    }

    /**
     * Get all directories in a directory.
     *
     * @param string $directory The directory path.
     *
     * @return array The directory paths.
     */
    public static function directories(string $directory): array
    {
        if (!self::isDirectory($directory)) {
            return [];
        }

        $items = glob($directory . '/*', GLOB_ONLYDIR);
        return $items ?: [];
    }

    /**
     * Create a directory.
     *
     * @param string $path The directory path.
     * @param int $mode The permissions' mode.
     * @param bool $recursive Whether to create nested directories.
     *
     * @return bool True on success.
     */
    public static function makeDirectory(string $path, int $mode = 0755, bool $recursive = true): bool
    {
        if (self::isDirectory($path)) {
            return true;
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Delete a directory.
     *
     * @param string $directory The directory path.
     * @param bool $preserve Whether to preserve the directory itself.
     *
     * @return bool True on success.
     */
    public static function deleteDirectory(string $directory, bool $preserve = false): bool
    {
        if (!self::isDirectory($directory)) {
            return false;
        }

        $items = array_diff(scandir($directory) ?: [], ['.', '..']);

        foreach ($items as $item) {
            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (self::isDirectory($path)) {
                self::deleteDirectory($path);
            } else {
                self::delete($path);
            }
        }

        return $preserve || rmdir($directory);
    }

    /**
     * Clean a directory (remove all contents).
     *
     * @param string $directory The directory path.
     *
     * @return bool True on success.
     */
    public static function cleanDirectory(string $directory): bool
    {
        return self::deleteDirectory($directory, true);
    }

    /**
     * Get file contents as an array of lines.
     *
     * @param string $path The file path.
     * @param bool $skipEmpty Whether to skip empty lines.
     *
     * @return array The lines.
     */
    public static function lines(string $path, bool $skipEmpty = false): array
    {
        $contents = self::get($path);
        $lines = explode("\n", $contents);

        if ($skipEmpty) {
            $lines = array_filter($lines, static fn($line) => trim($line) !== '');
        }

        return array_values($lines);
    }

    /**
     * Get a hash of the file contents.
     *
     * @param string $path The file path.
     * @param string $algorithm The hash algorithm.
     *
     * @return string The hash.
     * @throws RuntimeException If hash cannot be generated.
     */
    public static function hash(string $path, string $algorithm = 'sha256'): string
    {
        if (!self::exists($path)) {
            throw new RuntimeException("File not found: $path");
        }

        $hash = hash_file($algorithm, $path);
        if ($hash === false) {
            throw new RuntimeException("Unable to hash file: $path");
        }

        return $hash;
    }

    /**
     * Replace content in a file.
     *
     * @param string $path The file path.
     * @param array|string $search The string(s) to search for.
     * @param array|string $replace The replacement string(s).
     *
     * @return int The number of bytes written.
     * @throws RuntimeException If file cannot be processed.
     */
    public static function replace(string $path, array|string $search, array|string $replace): int
    {
        $content = self::get($path);
        $newContent = str_replace($search, $replace, $content);
        return self::put($path, $newContent);
    }

    /**
     * Replace content in a file using regex.
     *
     * @param string $path The file path.
     * @param array|string $pattern The regex pattern(s).
     * @param array|string $replacement The replacement string(s).
     *
     * @return int The number of bytes written.
     * @throws RuntimeException If file cannot be processed.
     */
    public static function replaceRegex(string $path, array|string $pattern, array|string $replacement): int
    {
        $content = self::get($path);
        $newContent = preg_replace($pattern, $replacement, $content);

        if ($newContent === null) {
            throw new RuntimeException("Regex replacement failed: $path");
        }

        return self::put($path, $newContent);
    }

    /**
     * Check if a file contains a string.
     *
     * @param string $path The file path.
     * @param string $needle The string to search for.
     *
     * @return bool True if found, false otherwise.
     */
    public static function contains(string $path, string $needle): bool
    {
        $content = self::get($path);
        return str_contains($content, $needle);
    }

    /**
     * Get file contents as JSON decoded array/object.
     *
     * @param string $path The file path.
     * @param bool $associative Whether to return associative array.
     *
     * @return mixed The decoded JSON.
     * @throws RuntimeException If JSON cannot be decoded.
     */
    public static function json(string $path, bool $associative = true): mixed
    {
        $content = self::get($path);
        $decoded = Json::decode($content, $associative);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON in file: $path");
        }

        return $decoded;
    }

    /**
     * Write data to a file as JSON.
     *
     * @param string $path The file path.
     * @param mixed $data The data to encode.
     * @param int $flags JSON encode flags.
     *
     * @return int The number of bytes written.
     * @throws RuntimeException If JSON cannot be encoded.
     */
    public static function putJson(string $path, mixed $data, int $flags = JSON_PRETTY_PRINT): int
    {
        $json = Json::encode($data, $flags);

        return self::put($path, $json);
    }

    /**
     * Get relative path from one file to another.
     *
     * @param string $from The starting path.
     * @param string $to The destination path.
     *
     * @return string The relative path.
     */
    public static function relativePath(string $from, string $to): string
    {
        $from = Str::replace('\\', '/', realpath($from) ?: $from);
        $to =  Str::replace('\\', '/', realpath($to) ?: $to);

        $fromParts = explode('/', trim($from, '/'));
        $toParts = explode('/', trim($to, '/'));

        foreach ($fromParts as $depth => $part) {
            if (isset($toParts[$depth]) && $part === $toParts[$depth]) {
                unset($fromParts[$depth], $toParts[$depth]);
            } else {
                break;
            }
        }

        return Str::repeat('../', count($fromParts)) . implode('/', $toParts);
    }

    /**
     * Ensure a file exists, create if it doesn't.
     *
     * @param string $path The file path.
     * @param string $contents Initial contents if creating.
     *
     * @return bool True if created, false if already existed.
     */
    public static function ensureExists(string $path, string $contents = ''): bool
    {
        if (self::exists($path)) {
            return false;
        }

        $directory = self::dirname($path);
        if (!self::isDirectory($directory)) {
            self::makeDirectory($directory);
        }

        self::put($path, $contents);
        return true;
    }

    /**
     * Get human readable file size.
     *
     * @param string $path The file path.
     * @param int $precision The decimal precision.
     *
     * @return string The formatted size.
     */
    public static function humanSize(string $path, int $precision = 2): string
    {
        $bytes = self::size($path);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Check if file is older than given seconds.
     *
     * @param string $path The file path.
     * @param int $seconds The age threshold in seconds.
     *
     * @return bool True if older, false otherwise.
     */
    public static function isOlderThan(string $path, int $seconds): bool
    {
        return (time() - self::lastModified($path)) > $seconds;
    }

    /**
     * Copy entire directory to a new location.
     *
     * @param string $source The source directory.
     * @param string $destination The destination directory.
     *
     * @return bool True on success.
     */
    public static function copyDirectory(string $source, string $destination): bool
    {
        if (!self::isDirectory($source)) {
            throw new RuntimeException("Source directory not found: $source");
        }

        self::makeDirectory($destination);

        $items = array_diff(scandir($source) ?: [], ['.', '..']);

        foreach ($items as $item) {
            $srcPath = $source . DIRECTORY_SEPARATOR . $item;
            $dstPath = $destination . DIRECTORY_SEPARATOR . $item;

            if (self::isDirectory($srcPath)) {
                self::copyDirectory($srcPath, $dstPath);
            } else {
                self::copy($srcPath, $dstPath);
            }
        }

        return true;
    }

    /**
     * Move entire directory to a new location.
     *
     * @param string $source The source directory.
     * @param string $destination The destination directory.
     *
     * @return bool True on success.
     */
    public static function moveDirectory(string $source, string $destination): bool
    {
        self::copyDirectory($source, $destination);
        return self::deleteDirectory($source);
    }

    /**
     * Get the glob pattern matches.
     *
     * @param string $pattern The glob pattern.
     * @param int $flags The glob flags.
     *
     * @return array The matching paths.
     */
    public static function glob(string $pattern, int $flags = 0): array
    {
        return glob($pattern, $flags) ?: [];
    }

    /**
     * Check if path matches a pattern.
     *
     * @param string $pattern The pattern (supports * and ?).
     * @param string $path The path to check.
     *
     * @return bool True if matches, false otherwise.
     */
    public static function matchesPattern(string $pattern, string $path): bool
    {
        $regex = '/^' . str_replace(
                ['/', '*', '?'],
                ['\/', '.*', '.'],
                $pattern
            ) . '$/';

        return (bool) preg_match($regex, $path);
    }

    /**
     * Get the closest existing parent directory.
     *
     * @param string $path The path to check.
     *
     * @return string The closest existing directory.
     */
    public static function closestExistingDirectory(string $path): string
    {
        while (!self::isDirectory($path) && $path !== dirname($path)) {
            $path = dirname($path);
        }

        return $path;
    }
}