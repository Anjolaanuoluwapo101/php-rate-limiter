<?php

namespace PHPRateLimiter\Storage;

use Exception;
use PHPRateLimiter\Storage\Storage;

class StorageFactory extends Storage
{
    private static ?StorageInterface $fileStorageInstance = null;
    private static ?StorageInterface $redisStorageInstance = null;
    private static ?StorageInterface $databaseStorageInstance = null;
    private static ?StorageInterface $sessionStorageInstance = null;
    private static ?StorageInterface $analyticsStorageInstance = null;
    private static ?StorageInterface $rateLimiterStorageInstance = null;

    private function __construct()
    {
        $this->loadEnv();
    }

    public static function create(string $type = null): StorageInterface
    {
        
        (new StorageFactory)->loadEnv(); # Load environment variables

        $driver = $_ENV['STORAGE_DRIVER'] ?? 'sqlite';

        switch (strtolower($driver)) {
            case 'file':
                if (self::$fileStorageInstance === null) {
                    if ($type === 'analytics') {
                        $filePath = $_ENV['ANALYTICS_STORAGE_FILE_NAME'] ?? 'analytics_storage.json';
                        return new FileStorage($filePath);
                    } elseif ($type === 'ratelimiter') {
                        $filePath = $_ENV['RATELIMITER_STORAGE_FILE_NAME'] ?? 'ratelimiter_storage.json';
                        return new FileStorage($filePath);
                    } else {
                        throw new Exception('Invalid instantiation of the FileStorage Class.');
                    }
                }
                return self::$fileStorageInstance;
            case 'database':
                if ($type === 'analytics') {
                    if (self::$analyticsStorageInstance === null) {
                        self::$analyticsStorageInstance = new DatabaseStorage($type);
                    }
                    return self::$analyticsStorageInstance;
                } elseif ($type === 'ratelimiter') {
                    if (self::$rateLimiterStorageInstance === null) {
                        self::$rateLimiterStorageInstance = new DatabaseStorage($type);
                    }
                    return self::$rateLimiterStorageInstance;
                } else {
                    throw new Exception('Invalid instantiation of the DatabaseStorage Class.');
                }
            case 'session':
                if(self::$sessionStorageInstance === null){
                    self::$sessionStorageInstance =  new SessionStorage();
                }
                return self::$sessionStorageInstance;
            case 'redis':
            default:
                if (self::$redisStorageInstance === null) {
                    self::$redisStorageInstance = new RedisStorage();
                }
                return self::$redisStorageInstance;
        }
    }


}
