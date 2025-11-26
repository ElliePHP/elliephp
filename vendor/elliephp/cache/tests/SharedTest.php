<?php

namespace ElliePHP\Components\Cache\Tests;

use DateInterval;
use ElliePHP\Components\Cache\Exceptions\InvalidArgumentException;
use ElliePHP\Components\Cache\Shared;
use PHPUnit\Framework\TestCase;

class SharedTest extends TestCase
{
    public function testConvertTtlToSecondsWithNull(): void
    {
        $this->assertSame(0, Shared::convertTtlToSeconds(null));
    }

    public function testConvertTtlToSecondsWithInteger(): void
    {
        $this->assertSame(3600, Shared::convertTtlToSeconds(3600));
    }

    public function testConvertTtlToSecondsWithNegativeInteger(): void
    {
        $this->assertSame(0, Shared::convertTtlToSeconds(-100));
    }

    public function testConvertTtlToSecondsWithDateInterval(): void
    {
        $interval = new DateInterval('PT1H');
        $seconds = Shared::convertTtlToSeconds($interval);
        $this->assertSame(3600, $seconds);
    }

    public function testConvertTtlToSecondsWithLargeValue(): void
    {
        $result = Shared::convertTtlToSeconds(PHP_INT_MAX);
        $this->assertSame(2147483647, $result);
    }

    public function testIterableToArrayWithArray(): void
    {
        $array = ['a', 'b', 'c'];
        $this->assertSame($array, Shared::iterableToArray($array));
    }

    public function testIterableToArrayWithIterator(): void
    {
        $iterator = new \ArrayIterator(['a', 'b', 'c']);
        $this->assertSame(['a', 'b', 'c'], Shared::iterableToArray($iterator));
    }

    public function testValidateKeyWithValidKey(): void
    {
        $this->expectNotToPerformAssertions();
        Shared::validateKey('valid_key');
    }

    public function testValidateKeyWithEmptyKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key cannot be empty');
        Shared::validateKey('');
    }

    public function testValidateKeyWithTooLongKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache key cannot exceed 255 characters');
        Shared::validateKey(str_repeat('a', 256));
    }

    public function testValidateKeyWithReservedCharacters(): void
    {
        $invalidKeys = ['{key}', 'key()', 'key/path', 'key\\path', 'key@host', 'key:value'];

        foreach ($invalidKeys as $key) {
            try {
                Shared::validateKey($key);
                $this->fail("Expected InvalidArgumentException for key: $key");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('reserved characters', $e->getMessage());
            }
        }
    }
}
