<?php
namespace Deljdlx\Github;

use Exception;

class Cache implements Interfaces\Cache
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function get(string $key): string|false
    {
        $fullPath = $this->path . $key . '.json';
        if(is_file($fullPath)) {
            return file_get_contents($fullPath);
        }
        return false;
    }

    public function set(string $key, mixed $data): void
    {
        $fullPath = $this->path . $key . '.json';
        $path = dirname($fullPath);

        if(!is_dir($path)) {
            mkdir($path, 0774, true);
        }

        if(!is_dir($path)) {
            throw new Exception('Could not create directory: ' . $path);
        }

        file_put_contents(
            $fullPath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }
}
