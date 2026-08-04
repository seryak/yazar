<?php

namespace Tests\Unit\Markdown\Extensions;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Renderer\Block\FencedCodeRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yazar\Markdown\Extensions\PhikiFencedCodeRenderer;
use Yazar\Markdown\Extensions\PhikiHighlightExtension;

#[CoversClass(PhikiHighlightExtension::class)]
class PhikiHighlightExtensionTest extends TestCase
{
    /**
     * {@see PhikiHighlightExtension::register()}
     */
    #[TestDox('register() overrides the default fenced code renderer')]
    public function test_overrides_default_fenced_code_renderer(): void
    {
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new PhikiHighlightExtension);

        $renderers = iterator_to_array($environment->getRenderersForClass(FencedCode::class));

        $this->assertInstanceOf(PhikiFencedCodeRenderer::class, $renderers[0]);
        $this->assertInstanceOf(FencedCodeRenderer::class, $renderers[1]);
    }
}
