<?php

namespace PHPRateLimiter\Storage;

interface StorageInterface
{
    public function get(string $key);
    public function set(string $key, $value, int $ttl = 0) ;
    public function increment(string $key);
    public function delete(string $key);
    public function closeConnection();
}
