<?php

use Deljdlx\Github\Cache;
use Deljdlx\Github\GithubClient;
use Deljdlx\Github\Readme;
use Deljdlx\Github\Repository;

require_once __DIR__ . '/vendor/autoload.php';


$options = getopt('', [
    'token:',
    'repository:',
]);

$token = $options['token'];
$repositoryName = $options['repository'];

$mainRepositoryName = 'deljdlx/deljdlx';


$client = new GithubClient($token);

$repositoryPath = __DIR__ . '/deljdlx';
if(is_dir($repositoryPath)) {
    exec('rm -rf ' . $repositoryPath);
}

$ownReadmePath = __DIR__ . '/../../../../README.md';

$readmeContent = file_get_contents($ownReadmePath);
if($readmeContent === false) {
    echo 'Could not read README.md' . PHP_EOL;
    exit(1);
}
$ownReadme = new Readme($readmeContent);
if(!$ownReadme->getDemoUrl()) {
    echo 'No demo url found in README.md' . PHP_EOL;
    exit(1);
}

$repository = $client->getRepository($repositoryName);

$demoUrl = $ownReadme->getDemoUrl();
$title = ($ownReadme->getTitle() !== false) ? $ownReadme->getTitle() : false;
$description = $ownReadme->getPart('SHORT-PRESENTATION');
if($title === false) {
    echo 'No title found in README.md' . PHP_EOL;
    exit(1);
}




$manager = $client->clone($mainRepositoryName, $repositoryPath);

$readmePath = $repositoryPath . '/README.md';
$content = file_get_contents($readmePath);
if($content === false) {
    echo 'Could not read README.md' . PHP_EOL;
    exit(1);
}
$readme = new Readme($content);


$partName = 'DEMO-' . $repository->getFullName();

$demoBuffer = '### [' . $title . '](' . $repository->getUrl() . ')' . PHP_EOL;
$demoBuffer .= $description . PHP_EOL;
$demoBuffer .= '👓 Demo: [' . $demoUrl . '](' . $demoUrl . ')' . PHP_EOL;

$result = $readme->appendPartToPart(
    'DEMOS',
    $partName,
    $demoBuffer
);

if(!$result) {
    echo 'Could not append to part DEMO' . PHP_EOL;
    echo 'Subpart name : '. $partName . PHP_EOL;
    echo 'Part content: ' . $demoBuffer . PHP_EOL;
    echo "========================================" . PHP_EOL;
    echo 'Content: ' . $readme->getContent() . PHP_EOL;
    echo "========================================" . PHP_EOL;

    exit(1);
}

$readme->write($readmePath);

echo 'Updating README.md. ' . $readmePath . PHP_EOL;
echo "======================================" . PHP_EOL;
echo $readme->compile() . PHP_EOL;
echo "======================================" . PHP_EOL;

$manager->add($readmePath);
$manager->commit('Update README.md - test');
$manager->push();

