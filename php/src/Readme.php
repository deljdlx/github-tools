<?php
namespace Deljdlx\Github;

class Readme
{
    private string $content;
    private $parts = [];

    public function __construct(string $content)
    {
        $this->content = $content;
        $this->parse($content);
    }

    public function getDemoUrl()
    {
        if(preg_match('`^.*?demo[^\n]*?\((https?://[^\s]+?)\).*?$`ism', $this->content, $matches)) {
            return $matches[1];
        }

        return false;
    }

    public function getTitle(): string|false
    {
        $pattern = '`# ([^\n]+?)\n`';
        preg_match($pattern, $this->content, $matches);
        return $matches[1] ?? false;
    }

    public function getContent(): string
    {
        return $this->content;
    }


    public function appendToPart(
        string $partName,
        string $content,
        bool $ifNotExists = true
    ): bool
    {
        if(!isset($this->parts[$partName])) {
            $this->parts[$partName] = '';
        }

        if($ifNotExists && !strpos($this->parts[$partName], $content)) {
            $this->parts[$partName] .= $content . PHP_EOL;
            return true;
        }

        return false;
    }

    public function getPart(string $partName): string
    {
        return $this->parts[$partName] ?? '';
    }

    public function compile()
    {
        $content = $this->content;
        foreach($this->parts as $partName => $partContent) {
            $content = preg_replace(
                '`<!--\s*<' . $partName . '>\s*-->(.*?)<!--\s*</' . $partName . '>-->`s',
                '<!--<' . $partName . '>-->' . $partContent . '<!--</' . $partName . '>-->',
                $content
            );
        }
        return $content;
    }


    public function parse(string $content): void
    {
        $pattern = '`<!--\s*<([^\s]+?)>\s*-->(.*?)<!--\s*<\/\\1>-->`s';

        preg_match_all(
            $pattern,
            $content,
            $matches,
            PREG_SET_ORDER
        );

        foreach($matches as $match) {
            $partName = $match[1];
            $partContent = $match[2];
            $this->parts[$partName] = $partContent;
        }
    }
}