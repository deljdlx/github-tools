<?php

use Deljdlx\Github\GithubClient;
use PHPUnit\Framework\TestCase;


class GithubClientTest extends TestCase
{
    public function testGetToken(): void
    {
        $client = new GithubClient('token', null);
        $this->assertEquals('token', $client->getToken());
    }

    public function testGetOwnRepositories(): void
    {
        $client = $this->getMockClient();
        $repositoriesData = $client->getOwnRepositories('deljdlx');

        $hasNextPage = $client->hasNextPage();

        $this->assertCount(18, $repositoriesData);
    }

    private function getMockClient(): GithubClient
    {
        $mock = $this->getMockBuilder(GithubClient::class)
            ->setConstructorArgs(['token', null]) // Passe les arguments du constructeur
            ->onlyMethods(['fetchGithubApi']) // Seule cette méthode est mockée
            ->getMock();

        $mock->method('fetchGithubApi')
            ->willReturnCallback(function($url) {
                $buffer = file_get_contents(__DIR__ . '/fixtures/repos.json');
                if (!is_string($buffer)) {
                    throw new Exception('Cannot read fixtures');
                }

                $data = json_decode($buffer, true);
                if (!is_array($data) || !isset($data['body'])) {
                    throw new Exception('Cannot decode fixtures');
                }

                return $data['body'];
            });

        return $mock;
    }
}
