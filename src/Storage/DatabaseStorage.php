<?php

namespace PHPRateLimiter\Storage;

use PDO;
use Exception;
use PHPRateLimiter\Storage\Storage;

class DatabaseStorage extends Storage implements StorageInterface
{
    private $pdo;
    protected string $table;
    protected bool $strictMode;

    public function __construct(string $type )
    {
        $this->loadEnv();
        $this->strictMode = ($_ENV['STORAGE_STRICT_MODE'] ?? 'false') === 'true'; //Error reporting

        $this->connect($type);
    }


    // Establish the database connection
    protected function connect($type): void
    {
        $dbDriver = $_ENV['DB_DRIVER'] ?? 'sqlite';  // Default to MySQL
        $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
        $dbName = $_ENV['DB_NAME'] ?? 'storage_db';
        $dbUser = $_ENV['DB_USER'] ?? 'root';
        $dbPass = $_ENV['DB_PASS'] ?? '';

        // SQLite specific setup
        if ($dbDriver === 'sqlite') {
            // echo $_SERVER['DOCUMENT_ROOT'];
            $basePath = $this->getProjectRoot();
            // die($basePath);
            $dbPath =  $basePath."/RateLimiterFiles/".$_ENV['DB_FILE_NAME'] ?? dirname(__DIR__) . '/StorageFiles/storage.sqlite'; // Default SQLite path if not provided
            if (!file_exists($dbPath)) {
                // Create empty SQLite file
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                touch($dbPath);
            }
            $dsn = "sqlite:$dbPath";
            $this->pdo = new PDO($dsn);
        } else {
            // For MySQL, PostgreSQL, etc.
            $dsn = "mysql:host=$dbHost;dbname=$dbName";
            $this->pdo = new PDO($dsn, $dbUser, $dbPass);
        }

        try {
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            if($this->strictMode)
                throw new Exception("Database connection failed: " . $e->getMessage());
        }

        if($type === 'ratelimiter'){
            $this->table = !empty($_ENV['RATELIMITER_TABLE']) ? $_ENV['RATELIMITER_TABLE'] : 'ratelimiter';
        }else if ($type === 'analytics'){
            $this->table = !empty($_ENV['ANALYTICS_TABLE']) ? $_ENV['ANALYTICS_TABLE'] : 'analytics';

        }
      
        // check if table exists or tries to create one
        if($this->createTable() === false ){
            if($this->strictMode)
                throw new Exception("Required Database tables does not exists. \n Failed to create the required Database tables");
        }
    }

    private function createTable(){
        $analytics = $this->pdo->exec("CREATE TABLE IF NOT EXISTS analytics ( `key` VARCHAR(255), `value` TEXT , `ttl` INT , PRIMARY KEY (`key`) ); ");
        $rateLimiter =  $this->pdo->exec("CREATE TABLE IF NOT EXISTS ratelimiter ( `key` VARCHAR(255), `value` TEXT , `ttl` INT , PRIMARY KEY (`key`) ); ");
        return ($analytics !== false && $rateLimiter !== false);
    }

    public function closeConnection(){
        $this->pdo = null; #close connection manually
        echo "Closed";
    }

    public function get(string $key)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `$this->table` WHERE `key` = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        // return $result ? unserialize($result['value']) : null;
        return $result ? $result : null;

    }

    public function set(string $key, $value, int $ttl = 0)
    {
        $dbDriver = $_ENV['DB_DRIVER'] ?? 'sqlite';
        if ($dbDriver === 'sqlite') {
            $stmt = $this->pdo->prepare("INSERT INTO `$this->table` (`key`, `value`, `ttl`) VALUES (:key, :value, :ttl)
                                         ON CONFLICT(`key`) DO UPDATE SET `value` = :value, `ttl` = :ttl");
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO `$this->table` (`key`, `value`, `ttl`) VALUES (:key, :value, :ttl)
                                         ON DUPLICATE KEY UPDATE `value` = :value, `ttl` = :ttl");
        }
        $stmt->execute([
            'key' => $key,
            'value' => $value,
            'ttl' => $ttl,
        ]);
    }

    public function increment(string $key)
    {
        $dbDriver = $_ENV['DB_DRIVER'] ?? 'sqlite';
        if ($dbDriver === 'sqlite') {
            // For SQLite
            $array = $this->get($key);
            if(is_null($array)){
                $this->set($key, 1 , time());
            }else{
                $newValue =  (int)$array['value']  + 1;
                $this->set($key, $newValue , $array['ttl']);
            }
        } else {
            //For SQL Relational Databases
            $stmt = $this->pdo->prepare("UPDATE `$this->table` SET `value` = value + 1 WHERE `key` = :key");
            $stmt->execute(['key' => $key]);
        }
    }

    public function delete(string $key)
    {
        $stmt = $this->pdo->prepare("DELETE FROM `$this->table` WHERE `key` = :key");
        $stmt->execute(['key' => $key]);
    }
}