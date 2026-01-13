<?php

namespace PHPRateLimiter\Storage;

use PHPRateLimiter\Storage\Storage;
use PHPRateLimiter\Storage\StorageInterface;
use Exception;

class FileStorage extends Storage implements StorageInterface
{
    protected string $filePath;
    protected array $data = [];
    protected bool $strictMode;
    protected int $lockTimeout;
    protected int $lockWait;

    public function __construct(?string $filePath = null)
    {
        $this->loadEnv();

        $this->strictMode = ($_ENV['STORAGE_STRICT_MODE'] ?? 'false') === 'true';
        $this->filePath = $filePath ?? ($_ENV['STORAGE_FILE_NAME'] ?? 'storage.json');
        $this->filePath = dirname(__DIR__)."/RateLimiterFiles/".$this->filePath;
        $this->lockTimeout = (int) ($_ENV['STORAGE_LOCK_TIMEOUT'] ?? 3);
        $this->lockWait = (int) ($_ENV['STORAGE_LOCK_WAIT'] ?? 200000);

        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }
    }


    protected function acquireLock($handle): bool
    {
        $start = time();
        while (!flock($handle, LOCK_EX)) {
            if ((time() - $start) > $this->lockTimeout) {
                return false;
            }
            usleep($this->lockWait);
        }
        return true;
    }

    public function closeConnection()
    {

    }

    protected function readFile(): mixed
    {
        $handle = fopen($this->filePath, 'c+');
        if (!$handle)
            throw new Exception("Failed to open file.");

        if (!$this->acquireLock($handle)) {
            fclose($handle);
            throw new Exception("Could not lock file for reading. \n This is due to the file being used by another process. \n Ensure no other process is using the file or increase the lock timeout.");
        }

        $content = '';
        foreach($this->streamFile($handle) as $chunk){ // create a generator to reduce memory overhead 
            $content .= $chunk;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            $data = [];
        }
        $this->data = $data;
        return $handle;
    }

    private function streamFile($handle ,int $chunkSize = 100){
        while(!feof($handle)){
            yield fread($handle , $chunkSize);
        }
        //handle is closed by the method that called readFile()
    }

    protected function writeFile(array $data): void
    {
        $handle = fopen($this->filePath, 'c+');
        if (!$handle)
            throw new Exception("Failed to open file for writing.");

        if (!$this->acquireLock($handle)) {
            fclose($handle);
            throw new Exception("Could not lock file for writing.");
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    public function get(string $key , $default = null): mixed
    {
        try {
            $handle = $this->readFile();
            $value = $this->data[$key] ?? $default;
            flock($handle, LOCK_UN);
            fclose($handle);
            return $value;
        } catch (Exception $e) {
            if ($this->strictMode)
                throw $e;
            return $default;
        }
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        try {
            $this->readFile();
            $this->data[$key] = 
                 [
                    'key' => $key,
                    'value' => $value,
                    'ttl' => $ttl
            ];
            $this->writeFile($this->data);
            return true;
        } catch (Exception $e) {
            if ($this->strictMode)
                throw $e;
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            $this->readFile();
            if (isset($this->data[$key])) {
                unset($this->data[$key]);
            }
            $this->writeFile($this->data);
            return true;
        } catch (Exception $e) {
            if ($this->strictMode)
                throw $e;
            return false;
        }
    }

    public function increment(string $key, int $step = 1): int
    {
        try {
            $this->readFile();
            $this->data[$key]['value'] = (int) ($this->data[$key]['value'] + $step) ;
            $this->writeFile($this->data);
            return $this->data[$key]['value'];
        } catch (Exception $e) {
            if ($this->strictMode)
                throw $e;
            return 0;
        }
    }
}
