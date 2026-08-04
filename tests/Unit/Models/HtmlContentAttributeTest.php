<?php

namespace Tests\Unit\Models;

use League\CommonMark\Extension\FrontMatter\FrontMatterParserInterface;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yazar\Markdown\MarkdownParser;
use Yazar\Models\Post;

class HtmlContentAttributeTest extends TestCase
{
    #[TestDox('html_content renders the stored markdown')]
    public function test_it_renders_stored_markdown(): void
    {
        $post = new Post(['content' => '# hello'.PHP_EOL.'world']);

        $this->assertSame("<h1>hello</h1>\n<p>world</p>\n", $post->html_content);
    }

    #[TestDox('html_content is rendered once per instance, not once per access')]
    public function test_it_is_memoized_per_instance(): void
    {
        $spy = new class(app(MarkdownConverter::class), app(FrontMatterParserInterface::class)) extends MarkdownParser
        {
            public int $calls = 0;

            public function toHtml(string $markdown): string
            {
                $this->calls++;

                return parent::toHtml($markdown);
            }
        };

        $this->app->instance(MarkdownParser::class, $spy);

        $post = new Post(['content' => '# hello']);

        $first = $post->html_content;
        $second = $post->html_content;
        $third = $post->html_content;

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
        $this->assertSame(1, $spy->calls);
    }

    #[TestDox('the parser is resolved from the container, so a host app can swap it')]
    public function test_parser_is_resolved_from_the_container(): void
    {
        $this->app->bind(MarkdownParser::class, fn (): MarkdownParser => new class(app(MarkdownConverter::class), app(FrontMatterParserInterface::class)) extends MarkdownParser
        {
            public function toHtml(string $markdown): string
            {
                return '<p>replaced</p>';
            }
        });

        $post = new Post(['content' => '# hello']);

        $this->assertSame('<p>replaced</p>', $post->html_content);
    }
}
