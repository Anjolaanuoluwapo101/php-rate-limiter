<?php


namespace PHPRateLimiter\Storage;

use Exception;


abstract class Storage{


    protected function loadEnv(): void
    {
        if (!isset($_ENV['STORAGE_DRIVER'])) {
            $projectRoot = $this->getProjectRoot();
            $dotenv = \Dotenv\Dotenv::createImmutable($projectRoot);
            $dotenv->load();
        }
    }

    protected function getProjectRoot(): string
    {
        // Try to locate the `composer.json` file in the directory or its parent directories
        //It uses composer.json to determine the root directory
        $dir = __DIR__;
        while (!file_exists($dir . '/composer.json') && $dir !== '/') {
            $dir = dirname($dir); // Go up one directory level
        }

        if ($dir === '/') {
            throw new Exception("Could not locate project root directory with composer.json. \n Ensure a composer.json file is present at the root directory of your project. \n If absent,create one. \n PHPRateLimiter uses this to dynamically determine your root directory");
        }

        return $dir;
    }
}