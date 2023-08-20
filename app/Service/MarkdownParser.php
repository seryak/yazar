<?php

namespace App\Service;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownParser
{
    protected MarkdownConverter $parser;
    public string $content;
    public array $options;


    /**
     * @test {@see \Tests\Unit\App\Service\MarkdownParser\BaseTest::testConstructor()}
     */
    public function __construct()
    {
        $config = [];
        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new FrontMatterExtension());

        $this->parser = new MarkdownConverter($environment);
    }

    /**
     * @test {@see \Tests\Unit\App\Service\MarkdownParser\BaseTest::testParse()}
     * @throws CommonMarkException
     */
    public function parse(string $string): void
    {
        $result = $this->parser->convert($string);
        $this->content = $result->getContent();
        $this->options = $result->getFrontMatter();
    }
}
