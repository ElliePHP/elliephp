<?php

namespace ElliePHP\Components\Cache;

/**
 * Available cache driver types
 */
readonly class CacheDrivers
{
    public const string REDIS = 'redis';
    
    /**
     * Valkey is Redis-compatible, so it uses the same driver
     */
    public const string VALKEY = 'redis';
    
    public const string FILE = 'file';
    public const string SQLITE = 'sqlite';
    public const string APCU = 'apcu';
}