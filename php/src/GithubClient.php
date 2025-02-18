<?php
namespace Deljdlx\Github;

use Deljdlx\Github\Interfaces\Cache;
use Deljdlx\Github\Interfaces\Repository as RepositoryInterface;
use Exception;

class GithubClient
{
    public const GITHUB_API_URL = 'https://api.github.com';
    private string $token;
    /**
     * @var array<string, string>
     */
    private array $lastResponseHeaders = [];
    private ?Cache $cacheDriver = null;

    public function __construct(string $token, ?Cache $cacheDriver = null)
    {
        $this->token = $token;
        $this->cacheDriver = $cacheDriver;
    }

    public function clone(string $repositoryName, string $path): RepositoryManager
    {
        $manager = new RepositoryManager($this, $path);
        $manager->clone($repositoryName, $path);

        return $manager;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getRepository(string $repositoryName, ?callable $factory = null): RepositoryInterface
    {
        $repositoryData = $this->fetchGithubApi('/repos/' . $repositoryName);
        $repository = $this->buildRepositoriesResponse(
            [$repositoryData],
            $factory,
            null
        );

        return $repository[0];
    }


    /**
     * @return array<RepositoryInterface>
     */
    public function getUserRepositories(string $userName, ?callable $factory = null): array
    {
        static $nextPageUrl;
        if($nextPageUrl === null) {
            $nextPageUrl = false;
        }


        $endpoint = '/users/' . $userName . '/repos';
        if($nextPageUrl !== false) {
            $endpoint = $nextPageUrl;
        }

        $repositories = $this->fetchGithubApi($endpoint);
        $userRepositories = $this->buildRepositoriesResponse(
            $repositories,
            $factory,
            null
        );
        $nextPageUrl = $this->getNextPageUrl();

        return $userRepositories;
    }

    /**
     * @return array<RepositoryInterface>
     */
    public function getOwnRepositories(string $userName, ?callable $factory = null): array
    {
        static $nextPageUrl;

        if($nextPageUrl === null) {
            $nextPageUrl = false;
        }

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

    public function hasNextPage(): bool
    {
        return
            isset($this->lastResponseHeaders['link'])
            && strpos($this->lastResponseHeaders['link'], 'rel="next"') !== false;
    }

    public function getNextPageUrl(): string|false
    {
        if(!array_key_exists('link', $this->lastResponseHeaders)) {
            return false;
        }

        $linkHeader = $this->lastResponseHeaders['link'];
        $matches = [];
        preg_match('/<([^>]+)>; rel="next"/', $linkHeader, $matches);
        if(!isset($matches[1])) {
            return false;
        }
        return $matches[1];
    }

    /**
     * @return array<string, mixed>
     */
    public function getLastResponseHeaders(): array
    {
        return $this->lastResponseHeaders;
    }

    /**
     * @param string $endpoint
     * @return array<mixed>
     */
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
                /**
                 * @var array{
                 *  headers: array<string, string>,
                 *  body: array<string, mixed>
                 * } $data
                 */
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
        if(!is_string($response)) {
            throw new Exception("Curl error: " . curl_error($ch));
        }
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

    /**
     * @param string $headers
     * @return array<string, string>
     */
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


    /**
     * @param array<mixed> $repositories
     * @param callable|null $factory
     * @param callable|null $filter
     * @return array<RepositoryInterface>
     */
    private function buildRepositoriesResponse(
        array $repositories,
        ?callable $factory = null,
        ?callable $filter = null
    ): array
    {
        $response = [];

        foreach ($repositories as $repositoryData) {
            if (!is_array($repositoryData)) {
                throw new Exception('Invalid repository data');
            }

            if ($filter !== null && !$filter($repositoryData)) {
                continue;
            }
                if($factory !== null) {
                    $response[] = $factory($repositoryData);
                    continue;
                }

                $response[] = new Repository($this, $repositoryData);
        }

        return $response;
    }

}