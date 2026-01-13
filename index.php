<?php


require __DIR__ . '/vendor/autoload.php';
use PHPRateLimiter\RateLimiter;

$maxAttempts = 5;      // Maximum allowed attempts
$decaySeconds = 60;    // Time window in seconds

$rateLimiter = new RateLimiter($maxAttempts, $decaySeconds);


$key = 'login_attempts:127.0.0.1'; //let us assume this is the key

if($rateLimiter->tooManyAttempts($key)){
    echo "Too many attempts. Please try again later.";
}else{
    echo "You may proceed.";
}
