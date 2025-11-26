<?php

namespace ElliePHP\Components\Cache\Tests;

use DateInterval;
use ElliePHP\Components\Cache\Drivers\FileDriver;
use ElliePHP\Components\Cache\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FileDriverTest extends TestCase
{
    private FileDriver $driver;
    private string $cachePath;

    protected function setUp(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/ellie_cache_test_' . uniqid() . '/';
        $this->driver = new FileDriver($this->cachePath);
    }

    protected function tearDown(): void
    {
        if (isset($this->driver)) {
            $this->driver->clear();
        }
        
        if (is_dir($this->cachePath)) {
            $files = glob($this->cachePath . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->cachePath);
        }
    }

    public function testSetAndGet(): void
    {
        $this->assertTrue($this->driver->set('test_key', 'test_value', 3600));
        $this->assertSame('test_value', $this->driver->get('test_key'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertSame('default', $this->driver->get('nonexistent', 'default'));
    }

    public function testHas(): void
    {
        $this->driver->set('test_key', 'test_value', 3600);
        $this->assertTrue($this->driver->has('test_key'));
        $this->assertFalse($this->driver->has('nonexistent'));
    }

    public function testDelete(): void
    {
        $this->driver->set('test_key', 'test_value', 3600);
        $this->assertTrue($this->driver->delete('test_key'));
        $this->assertNull($this->driver->get('test_key'));
    }

    public function testClear(): void
    {
        $this->driver->set('key1', 'value1', 3600);
        $this->driver->set('key2', 'value2', 3600);
        $this->assertTrue($this->driver->clear());
        $this->assertNull($this->driver->get('key1'));
        $this->assertNull($this->driver->get('key2'));
    }

    public function testExpiration(): void
    {
        $this->driver->set('test_key', 'test_value', 1);
        sleep(2);
        $this->assertNull($this->driver->get('test_key'));
    }

    public function testNoExpiration(): void
    {
        $this->driver->set('test_key', 'test_value', null);
        $this->assertSame('test_value', $this->driver->get('test_key'));
    }

    public function testGetMultiple(): void
    {
        $this->driver->set('key1', 'value1', 3600);
        $this->driver->set('key2', 'value2', 3600);

        $result = $this->driver->getMultiple(['key1', 'key2', 'key3'], 'default');

        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'default'
        ], $result);
    }

    public function testSetMultiple(): void
    {
        $values = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3'
        ];

        $this->assertTrue($this->driver->setMultiple($values, 3600));
        $this->assertSame('value1', $this->driver->get('key1'));
        $this->assertSame('value2', $this->driver->get('key2'));
        $this->assertSame('value3', $this->driver->get('key3'));
    }

    public function testDeleteMultiple(): void
    {
        $this->driver->set('key1', 'value1', 3600);
        $this->driver->set('key2', 'value2', 3600);
        $this->driver->set('key3', 'value3', 3600);

        $this->assertTrue($this->driver->deleteMultiple(['key1', 'key2']));
        $this->assertNull($this->driver->get('key1'));
        $this->assertNull($this->driver->get('key2'));
        $this->assertSame('value3', $this->driver->get('key3'));
    }

    public function testCount(): void
    {
        $this->driver->set('key1', 'value1', 3600);
        $this->driver->set('key2', 'value2', 3600);
        $this->assertSame(2, $this->driver->count());
    }

    public function testSize(): void
    {
        $this->driver->set('key1', 'value1', 3600);
        $this->assertGreaterThan(0, $this->driver->size());
    }

    public function testClearExpired(): void
    {
        $this->driver->set('key1', 'value1', 1);
        $this->driver->set('key2', 'value2', 3600);
        sleep(2);

        $cleared = $this->driver->clearExpired();
        $this->assertSame(1, $cleared);
        $this->assertNull($this->driver->get('key1'));
        $this->assertSame('value2', $this->driver->get('key2'));
    }

    public function testDateIntervalTtl(): void
    {
        $interval = new DateInterval('PT1H');
        $this->driver->set('test_key', 'test_value', $interval);
        $this->assertSame('test_value', $this->driver->get('test_key'));
    }

    public function testInvalidKeyDoesNotThrowInDriver(): void
    {
        // Drivers don't validate keys - that's done by the Cache wrapper
        // This test ensures drivers can handle any key format
        $this->assertTrue($this->driver->set('key:with:colons', 'value', 3600));
        $this->assertSame('value', $this->driver->get('key:with:colons'));
    }
}
