<?php

namespace ElliePHP\Components\Cache\Contracts;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;


interface CacheInterface extends SimpleCacheInterface
{
    public function count(): int;

    public function size(): int;
}