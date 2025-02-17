<?php
namespace Deljdlx\Github;

class RepositoryManager
{

    private GithubClient $client;
    private string $path;


    public function __construct(
        GithubClient $client,
        string $path
    ) {
        $this->client = $client;
        $this->path = $path;
    }

    public function add(string $path): void
    {
        $currentPath = getcwd();
        chdir($this->path);
        exec('git add ' . $path);
        chdir($currentPath);
    }

    public function commit(string $message): void
    {
        $currentPath = getcwd();
        chdir($this->path);
        exec('git commit -m "' . $message . '"');
        chdir($currentPath);
    }

    public function push(): void
    {
        $currentPath = getcwd();
        chdir($this->path);
        exec('git push');
        chdir($currentPath);
    }
}