<?php

namespace ElliePHP\Components\Cache\Tests;

use ElliePHP\Components\Cache\CacheDrivers;
use ElliePHP\Components\Cache\CacheFactory;
use ElliePHP\Components\Cache\Drivers\ApcuDriver;
use ElliePHP\Components\Cache\Drivers\FileDriver;
use ElliePHP\Components\Cache\Drivers\RedisDriver;
use ElliePHP\Components\Cache\Drivers\SQLiteDriver;
use ElliePHP\Components\Cache\Exceptions\CacheException;
use PHPUnit\Framework\TestCase;

class CacheFactoryTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ellie_cache_factory_test_' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->recursiveDelete($this->tempDir);
        }
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testCreateFileDriver(): void
    {
        $driver = CacheFactory::create(CacheDrivers::FILE, [
            'path' => $this->tempDir
        ]);

        $this->assertInstanceOf(FileDriver::class, $driver);
    }

    public function testCreateFileDriverWithoutPath(): void
    {
        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('File cache driver requires a "path" configuration');
        
        CacheFactory::create(CacheDrivers::FILE, []);
    }

    public function testCreateSQLiteDriver(): void
    {
        $driver = CacheFactory::create(CacheDrivers::SQLITE, [
            'path' => $this->tempDir . '/cache.db'
        ]);

        $this->assertInstanceOf(SQLiteDriver::class, $driver);
    }

    public function testCreateSQLiteDriverWithoutPath(): void
    {
        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('SQLite cache driver requires a "path" configuration');
        
        CacheFactory::create(CacheDrivers::SQLITE, []);
    }

    public function testCreateApcuDriver(): void
    {
        if (!extension_loaded('apcu')) {
            $this->markTestSkipped('APCu extension not available');
        }

        $driver = CacheFactory::create(CacheDrivers::APCU);
        $this->assertInstanceOf(ApcuDriver::class, $driver);
    }

    public function testCreateApcuDriverWithoutExtension(): void
    {
        if (extension_loaded('apcu')) {
            $this->markTestSkipped('APCu extension is available');
        }

        $this->expectException(CacheException::class);
        CacheFactory::create(CacheDrivers::APCU);
    }

    public function testCreateWithInvalidDriver(): void
    {
        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('Invalid cache driver specified: invalid');
        
        CacheFactory::create('invalid', []);
    }

    public function testCreateWithDefaultDriver(): void
    {
        $driver = CacheFactory::create(null, [
            'path' => $this->tempDir
        ]);

        $this->assertInstanceOf(FileDriver::class, $driver);
    }

    public function testCreateFileDriverMethod(): void
    {
        $driver = CacheFactory::createFileDriver([
            'path' => $this->tempDir
        ]);

        $this->assertInstanceOf(FileDriver::class, $driver);
    }

    public function testCreateSQLiteDriverMethod(): void
    {
        $driver = CacheFactory::createSQLiteDriver([
            'path' => $this->tempDir . '/cache.db'
        ]);

        $this->assertInstanceOf(SQLiteDriver::class, $driver);
    }

    public function testCreateApcuDriverMethod(): void
    {
        if (!extension_loaded('apcu')) {
            $this->markTestSkipped('APCu extension not available');
        }

        $driver = CacheFactory::createApcuDriver();
        $this->assertInstanceOf(ApcuDriver::class, $driver);
    }

    public function testFileDriverCreatesDirectory(): void
    {
        $path = $this->tempDir . '/nested/cache/';
        
        $driver = CacheFactory::createFileDriver([
            'path' => $path,
            'create_directory' => true
        ]);

        $this->assertInstanceOf(FileDriver::class, $driver);
        $this->assertDirectoryExists($path);
    }

    public function testSQLiteDriverCreatesDirectory(): void
    {
        $path = $this->tempDir . '/nested/cache.db';
        
        $driver = CacheFactory::createSQLiteDriver([
            'path' => $path,
            'create_directory' => true
        ]);

        $this->assertInstanceOf(SQLiteDriver::class, $driver);
        $this->assertDirectoryExists(dirname($path));
    }
}
