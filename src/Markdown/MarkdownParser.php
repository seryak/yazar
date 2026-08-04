<?php

namespace Yazar\Markdown;

use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\FrontMatter\FrontMatterParserInterface;
use League\CommonMark\MarkdownConverter;
use Yazar\Markdown\Extensions\DiskUrlResolutionException;

class MarkdownParser
{
    public string $markdownContent;

    /** @var array<string, mixed> */
    public array $options;

    public function __construct(
        protected readonly MarkdownConverter $parser,
        protected readonly FrontMatterParserInterface $frontMatterParser,
    ) {}

    /**
     * @throws CommonMarkException|DiskUrlResolutionException
     */
    public function parse(string $string): void
    {
        $input = $this->frontMatterParser->parse($string);
        $this->parser->convert($input->getContent());

        $frontMatter = $input->getFrontMatter();

        $this->markdownContent = $input->getContent();
        $this->options = is_array($frontMatter) ? $frontMatter : [];
    }
    public function toHtml(string $markdown): string
    {
        return $this->parser->convert($markdown)->getContent();
    }
}
