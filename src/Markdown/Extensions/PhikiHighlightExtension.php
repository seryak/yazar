<?php

namespace Yazar\Markdown\Extensions;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\ExtensionInterface;

class PhikiHighlightExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(
            FencedCode::class,
            new PhikiFencedCodeRenderer(
                config('yazar.markdown.phiki.theme', 'github-light'),
                config('yazar.markdown.phiki.default_grammar', 'shellscript'),
            ),
            10,
        );
    }
}
