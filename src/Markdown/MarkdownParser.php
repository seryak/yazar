<?php

namespace Yazar\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterParserInterface;
use League\CommonMark\MarkdownConverter;
use Tests\Unit\App\Service\MarkdownParser\BaseTest;

class MarkdownParser
{
    protected MarkdownConverter $parser;

    protected FrontMatterParserInterface $frontMatterParser;

    public string $content;

    public string $markdownContent;

    /** @var array<string, mixed> */
    public array $options;

    /**
     * @test {@see BaseTest::testConstructor()}
     */
    public function __construct()
    {
        $config = [];
        $environment = new Environment($config);
        $frontMatterExtension = new FrontMatterExtension;

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension($frontMatterExtension);

        $this->parser = new MarkdownConverter($environment);
        $this->frontMatterParser = $frontMatterExtension->getFrontMatterParser();
    }

    /**
     * @test {@see BaseTest::testParse()}
     *
     * @throws CommonMarkException
     */
    public function parse(string $string): void
    {
        $input = $this->frontMatterParser->parse($string);
        $result = $this->parser->convert($input->getContent());

        $frontMatter = $input->getFrontMatter();

        $this->content = $result->getContent();
        $this->markdownContent = $input->getContent();
        $this->options = is_array($frontMatter) ? $frontMatter : [];
    }
}
