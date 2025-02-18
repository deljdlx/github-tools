<?php
namespace Deljdlx\Github;

use Exception;

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

    public function clone(string $repositoryName, string $path): RepositoryManager
    {
        $url = sprintf(
            'https://%s@github.com/%s.git',
            $this->client->getToken(),
            $repositoryName
        );

        $command = sprintf(
            'git clone %s %s',
            $url,
            $path
        );
        exec($command, $output, $returnVar);
        $manager = new self($this->client, $path);

        return $manager;
    }

    public function add(string $path): void
    {
        $currentPath = getcwd();
        if(is_bool($currentPath)) {
            throw new Exception('Could not get current working directory');
        }

        chdir($this->path);
        exec('git add ' . $path);
        chdir($currentPath);
    }

    public function commit(string $message): void
    {
        $currentPath = getcwd();
        if(is_bool($currentPath)) {
            throw new Exception('Could not get current working directory');
        }

        chdir($this->path);
        exec('git commit -m "' . $message . '"');
        chdir($currentPath);
    }

    public function push(): void
    {
        $currentPath = getcwd();
        if(is_bool($currentPath)) {
            throw new Exception('Could not get current working directory');
        }
        chdir($this->path);
        exec('git push');
        chdir($currentPath);
    }
}