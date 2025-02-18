<?php

use Deljdlx\Github\Readme;
use PHPUnit\Framework\TestCase;

class ReadmeTest extends TestCase
{
    public function testGetTitle(): void
    {
        $readme = $this->getReadme();
        $this->assertEquals('Hello I am Julien Delsescaux', $readme->getTitle());
    }

    public function testPartExists(): void
    {
        $readme = $this->getReadme();
        $this->assertTrue($readme->partExists('PRESENTATION'));
    }

    public function testPartDoesNotExist(): void
    {
        $readme = $this->getReadme();
        $this->assertFalse($readme->partExists('DOES_NOT_EXIST'));
    }

    public function testGetPart(): void
    {
        $readme = $this->getReadme();
        $part = $readme->getPart('PRESENTATION');
        $this->assertStringContainsString('Welcome to my GitHub! I\'ve been developing and optimizing high-impact web applications for over 20 years. My approach is pragmatic, focused on efficiency and user experience.', $part);
    }

    public function testAppendToPart(): void
    {
        $readme = $this->getReadme();
        $readme->appendToPart('PRESENTATION', 'Hello World');

        $part = $readme->getPart('PRESENTATION');
        $this->assertStringContainsString(' focused on efficiency and user experience.' . "\n\n" . 'Hello World', $part);
    }

    public function testReplacePartContent(): void
    {
        $readme = $this->getReadme();
        $readme->replacePartContent('PRESENTATION', 'Hello World');

        $part = $readme->getPart('PRESENTATION');
        $this->assertEquals('Hello World', $part);
    }

    public function testAppendPartToPart(): void
    {
        $readme = $this->getReadme();

        $readme->appendPartToPart('PRESENTATION', 'NEW-PART', 'Hello World');
        $part = $readme->getPart('NEW-PART');

        $this->assertEquals('Hello World', $part);
        $this->assertStringContainsString($readme->getPart('NEW-PART'), $readme->getPart('PRESENTATION'));
        $this->assertStringContainsString($readme->getPart('<!--<NEW-PART>-->'), $readme->getPart('PRESENTATION'));
        $this->assertStringContainsString($readme->getPart('<!--</NEW-PART>-->'), $readme->getPart('PRESENTATION'));
    }

    public function testClearPart(): void
    {
        $readme = $this->getReadme();
        $readme->clearPart('PRESENTATION');
        $content = $readme->compile();

        $this->assertStringNotContainsString('Welcome to my GitHub!', $content);
        $this->assertStringContainsString('<!--<PRESENTATION>-->', $content);
        $this->assertStringContainsString('<!--<PRESENTATION>-->', $content);
    }

    public function testDeletePart(): void
    {
        $readme = $this->getReadme();
        $readme->deletePart('PRESENTATION');
        $content = $readme->compile();

        $this->assertFalse($readme->partExists('PRESENTATION'));
        $this->assertStringNotContainsString('Welcome to my GitHub!', $content);
        $this->assertStringNotContainsString('<!--<PRESENTATION>-->', $content);
        $this->assertStringNotContainsString('<!--</PRESENTATION>-->', $content);
    }

    private function getReadme(): Readme
    {
        $buffer = file_get_contents(__DIR__ . '/fixtures/readme.md');
        if(!is_string($buffer)) {
            throw new Exception('Cannot read fixtures');

        }

        return new Readme($buffer);
    }

}