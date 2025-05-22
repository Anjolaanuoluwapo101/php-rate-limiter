# PHP Rate Limiter

A PHP Library that seamlessly integrates rate limiting into your platform. It supports multiple storage backends including Redis, File System, and SQL Relational Databases, providing flexible and efficient request limiting capabilities.

---

## Table of Contents

- [Introduction](#introduction)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [RateLimiter](#ratelimiter)
  - [Analytics](#analytics)
- [Storage Options](#storage-options)
- [Examples](#examples)
- [License](#license)
- [Author](#author)

---

## Introduction

The PHP Rate Limiter library provides a simple and extensible way to limit the number of requests or actions a user or system can perform within a specified time window. It supports multiple storage backends to suit different environments and scales, including Redis, file-based storage, SQL databases, and session storage.

Additionally, the library includes an Analytics component to track custom events, allowing you to monitor usage patterns or other metrics alongside rate limiting.

---

## Installation

You can install the library via Composer:

```bash
composer require anjola/php_rate_limiter
```

### Requirements

- PHP 7.2.5 or higher
- Extensions:
  - `fileinfo`
- Dependencies:
  - `vlucas/phpdotenv` for environment variable management
  - `predis/predis` for Redis support

---

## Configuration

The library uses environment variables to configure storage options. You can create a `.env` file in your project root to set these variables.

### Important Environment Variables

| Variable                    | Description                                         | Default                  |
|-----------------------------|-----------------------------------------------------|--------------------------|
| `STORAGE_DRIVER`             | Storage backend to use (`redis`, `file`, `database`, `session`) | `sqlite` (file-based)    |
| `ANALYTICS_STORAGE_FILE_NAME`| File name for analytics storage when using file driver | `analytics_storage.json` |
| `RATELIMITER_STORAGE_FILE_NAME`| File name for rate limiter storage when using file driver | `ratelimiter_storage.json` |

Make sure to configure your environment according to your preferred storage backend.

---

## Usage

### RateLimiter

The `RateLimiter` class allows you to limit the number of attempts for a given key within a decay period.

#### Initialization

```php
use PHPRateLimiter\RateLimiter;

$maxAttempts = 5;      // Maximum allowed attempts
$decaySeconds = 60;    // Time window in seconds

$rateLimiter = new RateLimiter($maxAttempts, $decaySeconds);
```

#### Check if too many attempts

```php
$key = 'user_ip_or_identifier';

if ($rateLimiter->tooManyAttempts($key)) {
    // Handle rate limit exceeded (e.g., block request, show error)
} else {
    // Proceed with the request
}
```

#### Clear records for a key

```php
$rateLimiter->clearRecords($key);
```

---

### Analytics

The `Analytics` class allows you to track custom events and retrieve their counts.

#### Initialization

```php
use PHPRateLimiter\Analytics;

$prefix = 'myapp_';  // Optional prefix for event keys
$analytics = new Analytics($prefix);
```

#### Track an event

```php
$event = 'homepage_visit';
$count = $analytics->trackEvent($event);
echo "Event count: " . $count;
```

#### Get event count

```php
$count = $analytics->getEventCount($event);
```

#### Reset event count

```php
$analytics->resetEvent($event);
```

---

## Storage Options

The library supports multiple storage backends to store rate limiting and analytics data. You can configure the storage driver via the `STORAGE_DRIVER` environment variable.

### Supported Drivers

- **Redis** (default)
  - Requires Redis server and `predis/predis` package.
  - High performance and suitable for distributed environments.

- **File System**
  - Stores data in JSON files.
  - Configurable file names via environment variables.
  - Suitable for simple or local setups.

- **Database**
  - Supports SQL relational databases.
  - Requires appropriate database setup and configuration.

- **Session**
  - Uses PHP session storage.
  - Suitable for per-session rate limiting.

### Configuration Example

Set the storage driver in your `.env` file:

```env
STORAGE_DRIVER=redis
```

Or for file storage:

```env
STORAGE_DRIVER=file
RATELIMITER_STORAGE_FILE_NAME=ratelimiter_storage.json
ANALYTICS_STORAGE_FILE_NAME=analytics_storage.json
```

---

## Examples

### Basic Rate Limiting Example

```php
require 'vendor/autoload.php';

use PHPRateLimiter\RateLimiter;

$rateLimiter = new RateLimiter(10, 60); // 10 attempts per 60 seconds
$key = $_SERVER['REMOTE_ADDR'];

if ($rateLimiter->tooManyAttempts($key)) {
    header('HTTP/1.1 429 Too Many Requests');
    echo "You have exceeded the maximum number of requests. Please try again later.";
    exit;
}

// Proceed with your application logic here
```

### Analytics Tracking Example

```php
require 'vendor/autoload.php';

use PHPRateLimiter\Analytics;

$analytics = new Analytics('app_');
$analytics->trackEvent('user_signup');

echo "User signup count: " . $analytics->getEventCount('user_signup');
```

---

## License

This project is licensed under the Apache-2.0 License. See the [LICENSE](LICENSE) file for details.

---

## Author

**Anjolaanuoluwapo**  
Email: anjolaakinsoyinu@gmail.com

---

Thank you for using PHP Rate Limiter! If you have any questions or issues, feel free to open an issue or contact the author.
