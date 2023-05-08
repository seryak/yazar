<?php

namespace Tests\Unit\App\Service\MarkdownParser;

use App\Service\MarkdownParser;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\MarkdownConverter;
use Tests\TestCase;

class BaseTest extends TestCase
{
    /**
     * {@see MarkdownParser::__construct()}
     */
    public function testConstructor(): void
    {
        $object = new MarkdownParser;
        $reflectedClass = new \ReflectionClass($object);
        $reflection = $reflectedClass->getProperty('parser');
        $reflection->setAccessible(true);

        /** @var MarkdownConverter $parser */
        $parser = $reflection->getValue($object);

        $this->assertEquals(MarkdownConverter::class, get_class($parser));

        $this->assertEquals(CommonMarkCoreExtension::class, get_class($parser->getEnvironment()->getExtensions()[0]));
        $this->assertEquals(FrontMatterExtension::class, get_class($parser->getEnvironment()->getExtensions()[1]));
    }

    /**
     * {@see MarkdownParser::parse()}
     */
    public function testParse(): void
    {
        $parserString = <<<EOT
        ---
        view::extends: layout
        view::section: content
        title: test
        created_at : 2022-05-06
        description: test description
        cover_image: /assets/post_covers/test.png
        ---
        # testtext
        olololo
        EOT;

        $object = new MarkdownParser();
        $object->parse($parserString);

        $this->assertEquals('layout', $object->options['view::extends']);
        $this->assertEquals('content', $object->options['view::section']);
        $this->assertEquals('test', $object->options['title']);
        $this->assertEquals('1651795200', $object->options['created_at']);
        $this->assertEquals('test description', $object->options['description']);
        $this->assertEquals('/assets/post_covers/test.png', $object->options['cover_image']);

        $assertContentString = <<<EOT
        <h1>testtext</h1>\n<p>olololo</p>\n
        EOT;

        $this->assertEquals($assertContentString, $object->content);
    }

    /**
     * {@see MarkdownParser::parse()}
     */
    public function testParseFails(): void
    {
        $parserString = <<<EOT
        # testtext
        olololo
        EOT;

        $this->expectExceptionMessage('Call to undefined method League\CommonMark\Output\RenderedContent::getFrontMatter()');
        $object = new MarkdownParser();
        $object->parse($parserString);
    }
}
