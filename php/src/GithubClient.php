<?php
namespace Deljdlx\Github;

use Deljdlx\Github\Interfaces\Cache;
use Exception;

class GithubClient
{
    public const GITHUB_API_URL = 'https://api.github.com';
    private string $token;
    private array $lastResponseHeaders = [];

    private ?string $nextPageUrl = null;

    private ?Cache $cacheDriver = null;

    public function __construct(string $token, ?Cache $cacheDriver = null)
    {
        $this->token = $token;
        $this->cacheDriver = $cacheDriver;
    }

    public function clone($repositoryName, $path): RepositoryManager
    {
        $url = sprintf(
            'https://%s@github.com/%s.git',
            $this->token,
            $repositoryName
        );

        $command = sprintf(
            'git clone %s %s',
            $url,
            $path
        );
        exec($command, $output, $returnVar);
        $manager = new RepositoryManager($this, $path);

        return $manager;
    }


    public function getUserRepositories(string $userName, ?callable $factory = null): array
    {
        $endpoint = '/users/' . $userName . '/repos';
        if($this->nextPageUrl !== null) {
            $endpoint = $this->nextPageUrl;
        }

        $repositories = $this->fetchGithubApi($endpoint);
        $userRepositories = $this->buildRepositoriesResponse(
            $repositories,
            $factory,
            null
        );


        return $userRepositories;
    }

    /**
     * @return []
     */
    public function getOwnRepositories(string $userName, ?callable $factory = null): array
    {
        static $nextPageUrl = $nextPageUrl ?? false;
        $endpoint = '/user/repos';
        if($nextPageUrl !== false) {
            $endpoint = $nextPageUrl;
        }

        $repositories = $this->fetchGithubApi($endpoint);
        $ownRepositories = $this->buildRepositoriesResponse(
            $repositories,
            $factory,
            function($repositoryData) use ($userName) {
                return $repositoryData['owner']['login'] === $userName;
            }
        );
        $nextPageUrl = $this->getNextPageUrl();

        return $ownRepositories;
    }

    public function getNextPage(): array
    {
        $nextPageUrl = $this->getNextPageUrl();
        return $this->fetchGithubApi($nextPageUrl);
    }


    public function hasNextPage(): bool
    {
        return isset($this->lastResponseHeaders['link'])
        && strpos($this->lastResponseHeaders['link'], 'rel="next"') !== false;
    }

    public function getNextPageUrl(): string|false
    {
        $linkHeader = $this->lastResponseHeaders['link'];
        $matches = [];
        preg_match('/<([^>]+)>; rel="next"/', $linkHeader, $matches);
        if(!isset($matches[1])) {
            return false;
        }
        return $matches[1];
    }

    public function getLastResponseHeaders(): array
    {
        return $this->lastResponseHeaders;
    }

    public function fetchGithubApi(string $endpoint): array
    {
        $url = self::GITHUB_API_URL . $endpoint;
        if(strpos($endpoint, 'http') === 0) {
            $url = $endpoint;
        }

        $cacheKey = $url;
        $cacheKey = str_replace(self::GITHUB_API_URL, '', $cacheKey);

        if($this->cacheDriver) {
            $cachedBuffer = $this->cacheDriver->get($cacheKey);

            if($cachedBuffer !== false) {
                $data = json_decode($cachedBuffer, true);
                $this->lastResponseHeaders = $data['headers'];
                return $data['body'];
            }
        }

        // echo "\033[33mFetching {$url}\033[0m\n";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "User-Agent: PHP",
            "Authorization: token {$this->token}",
            "Accept: application/vnd.github.v3+json"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        $this->lastResponseHeaders = $this->parseHeaders($headers);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("GitHub API error(HTTP $httpCode)");
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new Exception("Invalid JSON");
        }

        if($this->cacheDriver) {
            $this->cacheDriver->set(
                $cacheKey,
                [
                    'headers' => $this->lastResponseHeaders,
                    'body' => $data
                ]
            );
        }

        return $data;
    }

    private function parseHeaders(string $headers): array
    {
        $headerLines = explode("\r\n", trim($headers));
        $parsedHeaders = [];

        foreach ($headerLines as $line) {
            if (strpos($line, ': ') !== false) {
                [$key, $value] = explode(': ', $line, 2);
                $parsedHeaders[$key] = $value;
            }
        }

        return $parsedHeaders;
    }

    private function buildRepositoriesResponse(array $repositories, ?callable $factory = null, ?callable $filter = null): array
    {
        $response = [];

        foreach ($repositories as $repositoryData) {
            if ($filter !== null && !$filter($repositoryData)) {
                continue;
            }
                if($factory !== null) {
                    $response[] = $factory($repositoryData);
                    continue;
                }
                $response[] = $repositoryData;
        }

        return $response;
    }

}