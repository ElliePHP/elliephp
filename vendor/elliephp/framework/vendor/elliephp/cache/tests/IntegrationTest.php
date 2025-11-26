<?php

namespace ElliePHP\Components\Cache\Tests;

use ElliePHP\Components\Cache\Cache;
use ElliePHP\Components\Cache\CacheFactory;
use ElliePHP\Components\Cache\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ellie_cache_integration_test_' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testCacheWithPrefixDoesNotThrowException(): void
    {
        // This test verifies the bug fix where prefixed keys with colons
        // were incorrectly validated by drivers
        $driver = CacheFactory::createFileDriver([
            'path' => $this->tempDir
        ]);

        $cache = new Cache($driver);

        // User provides a valid key
        $key = 'applemusic_de75';
        
        // Cache adds prefix "ellie_cache:" internally
        // Driver receives "ellie_cache:applemusic_de75" which contains ":"
        // This should NOT throw an exception
        $this->assertTrue($cache->set($key, 'test_value', 3600));
        $this->assertSame('test_value', $cache->get($key));
        $this->assertTrue($cache->has($key));
        $this->assertTrue($cache->delete($key));
    }

    public function testCacheValidatesUserProvidedKeys(): void
    {
        $driver = CacheFactory::createFileDriver([
            'path' => $this->tempDir
        ]);

        $cache = new Cache($driver);

        // User provides invalid key - should throw exception
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key contains reserved characters');
        
        $cache->set('invalid{key}', 'value', 3600);
    }

    public function testCacheValidatesEmptyKeys(): void
    {
        $driver = CacheFactory::createFileDriver([
            'path' => $this->tempDir
        ]);

        $cache = new Cache($driver);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key cannot be empty');
        
        $cache->get('');
    }

    public function testCacheValidatesLongKeys(): void
    {
        $driver = CacheFactory::createFileDriver([
            'path' => $this->tempDir
        ]);

        $cache = new Cache($driver);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key cannot exceed 255 characters');
        
        $cache->set(str_repeat('a', 256), 'value', 3600);
    }

    public function testMultipleOperationsWithPrefix(): void
    {
        $driver = CacheFactory::createFileDriver([
            'path' => $this->tempDir
        ]);

        $cache = new Cache($driver);

        // Set multiple values
        $values = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3'
        ];

        $this->assertTrue($cache->setMultiple($values, 3600));

        // Get multiple values
        $result = $cache->getMultiple(['key1', 'key2', 'key3']);
        
        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3'
        ], $result);

        // Delete multiple
        $this->assertTrue($cache->deleteMultiple(['key1', 'key2']));
        $this->assertNull($cache->get('key1'));
        $this->assertNull($cache->get('key2'));
        $this->assertSame('value3', $cache->get('key3'));
    }
}
