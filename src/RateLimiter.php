<?php

namespace PHPRateLimiter;

use PHPRateLimiter\Storage\StorageFactory;
use PHPRateLimiter\Storage\StorageInterface;


class RateLimiter
{
    protected StorageInterface $storage;
    protected int $maxAttempts;
    protected int $decaySeconds;

    public function __construct( int $maxAttempts = 5, int $decaySeconds = 60)
    {
        
        $this->storage = StorageFactory::create('ratelimiter');
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
    }

    public function tooManyAttempts(string $key): bool
    {
        $isRedis = get_class($this->storage) === 'PHPRateLimiter\Storage\RedisStorage';

        if ($isRedis) {
            $value = $this->storage->get($key);
            $value = $value !== null ? (int)$value : 0;

            if ($value === 0) {
                $this->storage->set($key, 1, $this->decaySeconds);
                return false;
            }

            if ($value < $this->maxAttempts) {
                $this->storage->increment($key);
                return false;
            } else {
                return true;
            }
        } else {
            $array = $this->storage->get($key);

            if(empty($array) || is_null($array) || !isset($array)){ 
                //for completely new entry
                $this->storage->set($key , 1 , time());
                return false;
            }

            if(time() - $array['ttl'] >= $this->decaySeconds){
                //if enough time has passed
                //reset the number of requests and the time 
                $this->storage->set($key , 1 , time());
                return false;
            }

            if($array['value'] < $this->maxAttempts){
                $array['value'] = (int) $array['value']  + 1; //increment the number of requests made
                $this->storage->increment($key);
                return false;
            }else{
                return true;
            }
        }
    }

    public function clearRecords(string $key): void
    {
        $this->storage->delete($key);
    }

    protected function closeConnection(){
        $this->storage->closeConnection();
    }
}
