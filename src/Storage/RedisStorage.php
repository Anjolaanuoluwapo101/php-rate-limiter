<?php

namespace PHPRateLimiter\Storage;

use PHPRateLimiter\Storage\StorageInterface;
use Predis\Client;

class RedisStorage implements StorageInterface
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['REDIS_PORT'] ?? 6379,
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'scheme' => $_ENV['REDIS_SCHEME'] ?? 'tcp',
        ]);
    }

    public function get(string $key)
    {
        return $this->client->get($key);
    }

    public function set(string $key, $value, int $ttl = 0)
    {
        $this->client->set($key, $value);
        if ($ttl > 0) {
            $this->client->expire($key, $ttl);
        }
    }

    public function increment(string $key)
    {
        return $this->client->incr($key);
    }

    public function delete(string $key)
    {
        $this->client->del([$key]);
    }

    public function closeConnection(){}
}
