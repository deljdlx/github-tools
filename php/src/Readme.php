<?php
namespace Deljdlx\Github;

class Readme
{
    private string $content = '';
    /**
     * @var array<string, string>
     */
    private $parts = [];

    public function __construct(string $content)
    {
        $this->content = $content;
        $this->parse($content);
    }

    public function write(string $file): int|false
    {
        return file_put_contents($file, $this->compile());
    }

    public function getDemoUrl(): string|false
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
        bool $ifNotExists = true,
        bool $endOfLine = true,
    ): bool
    {
        if(!isset($this->parts[$partName])) {
            $this->parts[$partName] = '';
        }

        if($ifNotExists && !strpos($this->parts[$partName], $content)) {
            $this->parts[$partName] .= $content;
            if($endOfLine) {
                $this->parts[$partName] .= PHP_EOL;
            }
            return true;
        }

        return false;
    }

    public function appendPartToPart(
        string $partName,
        string $subPartName,
        string $content,
    ): bool
    {
        if(!$this->partExists($partName)) {
            return false;
        }

        if($this->partExists($subPartName)) {
            $this->replacePartContent($subPartName, $content);
            return true;
        }

        $this->parts[$subPartName] = PHP_EOL . $content. PHP_EOL;
        $subPartContent = sprintf(
            '<!--<%s>-->' . PHP_EOL .
            '%s' . PHP_EOL .
            '<!--</%s>-->' . PHP_EOL,
            $subPartName,
            $content,
            $subPartName
        );
        return $this->appendToPart($partName, $subPartContent);
    }

    public function deletePart(string $partName): bool
    {
        if(array_key_exists($partName, $this->parts)) {
            unset($this->parts[$partName]);
            $newContent = preg_replace(
                '`<!--\s*<' . $partName . '>\s*-->(.*?)<!--\s*</' . $partName . '>-->`s',
                '',
                $this->content
            );

            if(!is_string($newContent)) {
                return false;
            }
            $this->content = $newContent;

            return true;
        }
        return false;
    }

    public function clearPart(string $partName): bool
    {
        if(array_key_exists($partName, $this->parts)) {
            $this->parts[$partName] = '';
            return true;
        }
        return false;
    }

    public function replacePartContent(
        string $partName,
        string $content,
    ): bool
    {
        if(array_key_exists($partName, $this->parts)) {
            $this->parts[$partName] = $content;
            return true;
        }
        return false;
    }

    public function partExists(string $partName): bool
    {
        return isset($this->parts[$partName]);
    }



    public function getPart(string $partName): string
    {
        return $this->parts[$partName] ?? '';
    }

    public function compile(): string
    {
        $content = $this->content;

        foreach($this->parts as $partName => $partContent) {
            $compiledPart = preg_replace(
                '`<!--\s*<' . $partName . '>\s*-->(.*?)<!--\s*</' . $partName . '>-->`s',
                '<!--<' . $partName . '>-->' . $partContent . '<!--</' . $partName . '>-->',
                $content
            );

            if(is_string($compiledPart)) {
                $content = $compiledPart;
            }
        }
        return $content;
    }


    public function parse(string $content): void
    {
        $pattern = '`<!--\s*<([^\s]+?)>\s*-->(.*?)<!--\s*<\/\\1>\s*-->`s';

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