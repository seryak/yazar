<?php

namespace Tests\Unit\Markdown\Extensions;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use ReflectionMethod;
use Tests\TestCase;
use Yazar\Markdown\Extensions\PhikiFencedCodeRenderer;

class PhikiFencedCodeRendererTest extends TestCase
{
    /**
     * {@see PhikiFencedCodeRenderer::detectGrammar()}
     */
    public function test_uses_language_from_info_string(): void
    {
        $renderer = new PhikiFencedCodeRenderer('github-light', 'shellscript');
        $node = $this->makeFencedCode('php', "echo 1;\n");

        $this->assertSame('php', $this->detectGrammar($renderer, $node));
    }

    /**
     * {@see PhikiFencedCodeRenderer::detectGrammar()}
     */
    public function test_uses_default_grammar_when_language_missing(): void
    {
        $renderer = new PhikiFencedCodeRenderer('github-light', 'shellscript');
        $node = $this->makeFencedCode(null, "ls /sys/class/backlight/\n");

        $this->assertSame('shellscript', $this->detectGrammar($renderer, $node));
    }

    /**
     * {@see PhikiFencedCodeRenderer::render()}
     */
    public function test_renders_highlighted_html(): void
    {
        $renderer = new PhikiFencedCodeRenderer('github-light', 'shellscript');
        $node = $this->makeFencedCode('php', "echo 1;\n");

        $html = $renderer->render($node, $this->createMock(ChildNodeRendererInterface::class));

        $this->assertIsString($html);
        $this->assertStringContainsString('<pre', $html);
    }

    private function makeFencedCode(?string $info, string $literal): FencedCode
    {
        $node = new FencedCode(3, '`', 0);

        if ($info !== null) {
            $node->setInfo($info);
        }

        $node->setLiteral($literal);

        return $node;
    }

    private function detectGrammar(PhikiFencedCodeRenderer $renderer, FencedCode $node): string
    {
        $method = new ReflectionMethod($renderer, 'detectGrammar');
        $method->setAccessible(true);

        return $method->invoke($renderer, $node);
    }
}
