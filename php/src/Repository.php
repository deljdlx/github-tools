<?php

namespace Deljdlx\Github;

use Exception;

class Repository
{
    public readonly array $repositoryData;
    private GithubClient $client;

    public function __construct(
        GithubClient $client,
        array $repositoryData
    ) {
        $this->client = $client;
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
        return preg_replace(
            '`[^a-z0-9]`',
            '-',
            $slug
        );
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
            if ($response === false) {
                return false;
            }

            return base64_decode($response['content']);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getDemoUrl()
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
