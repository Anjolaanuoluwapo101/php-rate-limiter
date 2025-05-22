<?php

namespace PHPRateLimiter\Storage;

use PHPRateLimiter\Storage\Storage;
use PHPRateLimiter\Storage\StorageInterface;

class SessionStorage extends Storage implements StorageInterface
{
    protected $sessionName = 'php-rate-limiter';
    protected $sessionKeyPrefix = 'rate_';

    public function __construct()
    {
        $this->loadEnv();
        
        // 1 hour (3600 seconds), or more
        ini_set('session.gc_maxlifetime', $_ENV['SESSION_LIFETIME']);
        // Optional: tune GC probability (chance GC runs)
        ini_set('session.gc_probability', 1); // 1%
        ini_set('session.gc_divisor', 100);  // 1/100 = 1% chance

        session_name($this->sessionName);

        session_set_cookie_params([
            'lifetime' => $_ENV['SESSION_LIFETIME'], // 30 days
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']), // true if HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            var_dump($_SESSION);
        }
    }

    public function get(string $key)
    {
        $fullKey = $this->sessionKeyPrefix . $key;
        if (isset($_SESSION[$fullKey])) {
            $item = $_SESSION[$fullKey];
            return $item;
        }else{
            //to migitate session data loss.We return a brand new entry this helps to prevent error where  get() method ought to return an array
            return null;
        }
    }

    public function set(string $key, $value, int $ttl = 0)
    {

        $_SESSION[$this->sessionKeyPrefix . $key] = [
            'key' => "$this->sessionKeyPrefix$key",
            'value' => $value,
            'ttl' => $ttl,
        ];
    }

    public function increment(string $key)
    {
        $array = $this->get($key) ?? 0;
        // var_dump($current);
        $newValue = $array['value'] + 1;
        $this->set($key, $newValue , $array['ttl'] ); // Use same TTL logic as before
        return $newValue;
    }

    public function delete(string $key)
    {
        unset($_SESSION[$this->sessionKeyPrefix . $key]);
    }

    public function closeConnection()
    {
        // Ensure session data is written
        session_write_close();
    }
}
