<?php

namespace PHPRateLimiter;

use PHPRateLimiter\Storage\StorageFactory;
use PHPRateLimiter\Storage\StorageInterface;



class Analytics
{
    protected StorageInterface $storage;
    protected string $prefix;

    public function __construct( string $prefix = '')
    {
        $this->storage = StorageFactory::create('analytics');
        $this->prefix = $prefix;
    }

    public function trackEvent(string $event): int
    {
        $isRedis = get_class($this->storage) === 'PHPRateLimiter\Storage\RedisStorage';

        if ($isRedis) {
            $value = $this->storage->get("$this->prefix:$event");
            $value = $value !== null ? (int)$value : 0;

            if ($value === 0) {
                $this->storage->set("$this->prefix:$event", 1,  time());
            } else {
                $this->storage->increment("$this->prefix:$event");
            }
        } else {
            $array = $this->storage->get("$this->prefix:$event");

            if(empty($array) || is_null($array) || !isset($array)){ 
                $attempts = 1;
                $this->storage->set("$this->prefix:$event" , $attempts , time());
            }else{
                $value = ((int) $array['value']) + 1;
                $this->storage->set("$this->prefix:$event" , $value , time());
            }
        }
        usleep(200000);
        return $this->getEventCount("homepage_visit");
    }

    public function getEventCount(string $event): int
    {
        $array = $this->storage->get("$this->prefix:$event");
        // var_dump($array);
        if(!isset($array) || $array === null){ 
            $count = 1;
        } else if (is_array($array) && isset($array['value'])) {
            $count = $array['value'];
        } else if (!is_array($array) && is_int((int) $array)) {
            $count = $array;
        } else {
            $count = 1;
        }
        return $count;
    }

    public function resetEvent(string $event): void
    {
        $this->storage->delete("$this->prefix:$event");
    }

    // Retrieve all Events
}

