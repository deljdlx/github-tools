<?php

use Deljdlx\Github\Cache;
use Deljdlx\Github\GithubClient;
use Deljdlx\Github\Readme;
use Deljdlx\Github\Repository;

require_once __DIR__ . '/vendor/autoload.php';


// $options = getopt('', [
//     'token:',
//     'repository:',
// ]);

// $token = $options['token'];
// $repositoryName = $options['repository'];

$mainRepositoryName = 'deljdlx/deljdlx';


$client = new GithubClient($token);

$repositoryPath = __DIR__ . '/deljdlx';
if(is_dir($repositoryPath)) {
    exec('rm -rf ' . $repositoryPath);
}

$ownReadmePath = __DIR__ . '/../../../../README.md';

$ownReadme = new Readme(file_get_contents($ownReadmePath));
if($ownReadme->getDemoUrl()) {

    $repository = $client->getRepository($repositoryName);

    $demoUrl = $ownReadme->getDemoUrl();
    $title = ($ownReadme->getTitle() !== false) ? $ownReadme->getTitle() : false;
    $description = $ownReadme->getPart('DESCRIPTION');
    if($title === false) {
        echo 'No title found in README.md' . PHP_EOL;
        exit(1);
    }

    $manager = $client->clone($mainRepositoryName, $repositoryPath);

    $readmePath = $repositoryPath . '/README.md';
    $readme = new Readme(file_get_contents($readmePath));

    $demoBuffer = '### [' . $title . '](' . $repository->getUrl() . ')' . PHP_EOL;
    $demoBuffer .= $description . PHP_EOL;
    $demoBuffer .= '👓 Demo: [' . $demoUrl . '](' . $demoUrl . ')' . PHP_EOL;

    $readme->appendToPart('DEMOS', $demoBuffer);
    file_put_contents($readmePath, $readme->compile());

    echo 'Adding to README.md ' . $demoBuffer . PHP_EOL;

    $manager->add($readmePath);
    $manager->commit('Update README.md - test');
    $manager->push();
}
