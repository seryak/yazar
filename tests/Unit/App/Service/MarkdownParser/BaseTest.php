<?php

namespace Tests\Unit\App\Service\MarkdownParser;

use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yazar\Markdown\MarkdownParser;

#[CoversMethod(MarkdownParser::class, 'parse')]
#[CoversMethod(MarkdownParser::class, 'toHtml')]
class BaseTest extends TestCase
{
    #[TestDox('the container builds MarkdownConverter with the CommonMark and FrontMatter extensions')]
    public function test_converter_binding(): void
    {
        $parser = app(MarkdownConverter::class);

        $this->assertEquals(CommonMarkCoreExtension::class, get_class($parser->getEnvironment()->getExtensions()[0]));
        $this->assertEquals(FrontMatterExtension::class, get_class($parser->getEnvironment()->getExtensions()[1]));
    }

    #[TestDox('the container applies extensions configured in the config')]
    public function test_converter_binding_applies_configured_extensions(): void
    {
        config(['yazar.markdown.extensions' => [NullCommonMarkExtension::class]]);

        $extensions = app(MarkdownConverter::class)->getEnvironment()->getExtensions();

        $this->assertEquals(CommonMarkCoreExtension::class, get_class($extensions[0]));
        $this->assertEquals(FrontMatterExtension::class, get_class($extensions[1]));
        $this->assertEquals(NullCommonMarkExtension::class, get_class($extensions[2]));
    }

    #[TestDox('the converter is shared, so the Environment is built once')]
    public function test_converter_is_a_singleton(): void
    {
        $this->assertSame(app(MarkdownConverter::class), app(MarkdownConverter::class));
    }

    #[TestDox('toHtml() renders markdown without running the front matter pass')]
    public function test_to_html(): void
    {
        $html = app(MarkdownParser::class)->toHtml('# testtext'.PHP_EOL.'olololo');

        $this->assertEquals("<h1>testtext</h1>\n<p>olololo</p>\n", $html);
    }

    /**
     * {@see MarkdownParser::parse()}
     */
    #[TestDox('parse() parses front matter and extracts the raw markdown content')]
    public function test_parse(): void
    {
        $parserString = <<<'EOT'
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

        $object = app(MarkdownParser::class);
        $object->parse($parserString);

        $this->assertEquals('layout', $object->options['view::extends']);
        $this->assertEquals('content', $object->options['view::section']);
        $this->assertEquals('test', $object->options['title']);
        $this->assertEquals('1651795200', $object->options['created_at']);
        $this->assertEquals('test description', $object->options['description']);
        $this->assertEquals('/assets/post_covers/test.png', $object->options['cover_image']);

        $assertMarkdownString = <<<'EOT'
        # testtext
        olololo
        EOT;

        $this->assertEquals($assertMarkdownString, $object->markdownContent);
    }

    /**
     * {@see MarkdownParser::parse()}
     */
    #[TestDox('parse() without front matter still extracts markdown content, leaving options empty')]
    public function test_parse_fails(): void
    {
        $parserString = <<<'EOT'
        # testtext
        olololo
        EOT;

        $object = app(MarkdownParser::class);
        $object->parse($parserString);

        $this->assertEquals('# testtext'.PHP_EOL.'olololo', $object->markdownContent);
        $this->assertEquals([], $object->options);
    }
}
