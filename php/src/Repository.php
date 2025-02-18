<?php

namespace Deljdlx\Github;

use Exception;

class Repository implements Interfaces\Repository
{
    /**
     * @var array{
     *  name: string,
     *  full_name: string,
     *  html_url: string,
     *  archived: bool,
     *  private: bool,
     *  default_branch: string
     * } $repositoryData
     */
    public readonly array $repositoryData;
    private GithubClient $client;

    /**
     * @param array<mixed>$repositoryData
     */
    public function __construct(
        GithubClient $client,
        array $repositoryData
    ) {
        $this->client = $client;

        if(
            !isset($repositoryData['name']) || !is_string($repositoryData['name'])
            || !isset($repositoryData['full_name']) || !is_string($repositoryData['full_name'])
            || !isset($repositoryData['html_url']) || !is_string($repositoryData['html_url'])
            || !isset($repositoryData['archived']) || !is_bool($repositoryData['archived'])
            || !isset($repositoryData['private']) || !is_bool($repositoryData['private'])
            || !isset($repositoryData['default_branch']) || !is_string($repositoryData['default_branch'])
        ) {
            throw new Exception('Invalid repository data');
        }
        $this->repositoryData = $repositoryData;
    }

    public function getName(): string
    {
        return $this->repositoryData['name'];
    }

    public function getFullName(): string
    {
        return $this->repositoryData['full_name'];
    }

    public function getSlug(): string
    {
        $slug = mb_strtolower($this->getFullName());

        $slug = preg_replace(
            '`[^a-z0-9]`',
            '-',
            $slug
        );

        if(!is_string($slug)) {
            throw new Exception('Could not create slug');
        }

        return $slug;
    }

    public function getUrl(): string
    {
        return $this->repositoryData['html_url'];
    }

    public function isArchived(): bool
    {
        return $this->repositoryData['archived'];
    }

    public function isPrivate(): bool
    {
        return $this->repositoryData['private'];
    }

    public function getMainBranch(): string
    {
        return $this->repositoryData['default_branch'];
    }

    public function getReadme(): string|false
    {

        try {
            $response = $this->client->fetchGithubApi(
                '/repos/' . $this->repositoryData['full_name'] . '/readme'
            );

            if(!isset($response['content']) || !is_string($response['content'])) {
                return false;
            }

            return base64_decode($response['content']);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getDemoUrl(): string|false
    {
        $readme = $this->getReadme();
        if ($readme === false) {
            return false;
        }

        if(preg_match('`^.*?demo[^\n]*?\((https?://[^\s]+?)\).*?$`ism', $readme, $matches)) {
            return $matches[1];
        }

        return false;
    }
}
