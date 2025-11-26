<?php

namespace ElliePHP\Components\Cache\Tests;

use DateInterval;
use ElliePHP\Components\Cache\Cache;
use ElliePHP\Components\Cache\Contracts\CacheInterface;
use ElliePHP\Components\Cache\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    private CacheInterface $mockDriver;
    private Cache $cache;

    protected function setUp(): void
    {
        $this->mockDriver = $this->createMock(CacheInterface::class);
        $this->cache = new Cache($this->mockDriver);
    }

    public function testGetAppliesPrefix(): void
    {
        $this->mockDriver
            ->expects($this->once())
            ->method('get')
            ->with('ellie_cache:test_key', null)
            ->willReturn('test_value');

        $result = $this->cache->get('test_key');
        $this->assertSame('test_value', $result);
    }

    public function testSetAppliesPrefix(): void
    {
        $this->mockDriver
            ->expects($this->once())
            ->method('set')
            ->with('ellie_cache:test_key', 'test_value', 3600)
            ->willReturn(true);

        $result = $this->cache->set('test_key', 'test_value', 3600);
        $this->assertTrue($result);
    }

    public function testSetWithEmptyStringReturnsFalse(): void
    {
        $this->mockDriver
            ->expects($this->never())
            ->method('set');

        $result = $this->cache->set('test_key', '', 3600);
        $this->assertFalse($result);
    }

    public function testDeleteAppliesPrefix(): void
    {
        $this->mockDriver
            ->expects($this->once())
            ->method('delete')
            ->with('ellie_cache:test_key')
            ->willReturn(true);

        $result = $this->cache->delete('test_key');
        $this->assertTrue($result);
    }

    public function testHasAppliesPrefix(): void
    {
        $this->mockDriver
            ->expects($this->once())
            ->method('has')
            ->with('ellie_cache:test_key')
            ->willReturn(true);

        $result = $this->cache->has('test_key');
        $this->assertTrue($result);
    }

    public function testClear(): void
    {
        $this->mockDriver
            ->expects($this->once())
            ->method('clear')
            ->willReturn(true);

        $result = $this->cache->clear();
        $this->assertTrue($result);
    }

    public function testCount(): void
    {
        $this->mockDriver
            ->expects($this->once())
            ->method('count')
            ->willReturn(5);

        $result = $this->cache->count();
        $this->assertSame(5, $result);
    }

    public function testSize(): void
    {
        $this->mockDriver
            ->expects($this->once())
            ->method('size')
            ->willReturn(1024);

        $result = $this->cache->size();
        $this->assertSame(1024, $result);
    }

    public function testGetMultipleAppliesPrefixAndRemovesIt(): void
    {
        $this->mockDriver
            ->expects($this->once())
            ->method('getMultiple')
            ->with(['ellie_cache:key1', 'ellie_cache:key2'], 'default')
            ->willReturn([
                'ellie_cache:key1' => 'value1',
                'ellie_cache:key2' => 'value2'
            ]);

        $result = $this->cache->getMultiple(['key1', 'key2'], 'default');
        
        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'value2'
        ], $result);
    }

    public function testSetMultipleAppliesPrefix(): void
    {
        $this->mockDriver
            ->expects($this->once())
            ->method('setMultiple')
            ->with([
                'ellie_cache:key1' => 'value1',
                'ellie_cache:key2' => 'value2'
            ], 3600)
            ->willReturn(true);

        $result = $this->cache->setMultiple(['key1' => 'value1', 'key2' => 'value2'], 3600);
        $this->assertTrue($result);
    }

    public function testDeleteMultipleAppliesPrefix(): void
    {
        $this->mockDriver
            ->expects($this->once())
            ->method('deleteMultiple')
            ->with(['ellie_cache:key1', 'ellie_cache:key2'])
            ->willReturn(true);

        $result = $this->cache->deleteMultiple(['key1', 'key2']);
        $this->assertTrue($result);
    }

    public function testInvalidKeyThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->get('');
    }

    public function testInvalidKeyInMultipleOperations(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->getMultiple(['valid_key', 'invalid{key}']);
    }
}
