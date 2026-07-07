<?php

namespace Yazar\Markdown\Extensions;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use Phiki\Adapters\CommonMark\CodeBlockRenderer;
use Phiki\Grammar\Grammar;
use Phiki\Theme\Theme;

class PhikiFencedCodeRenderer extends CodeBlockRenderer
{
    /**
     * @param  array<string, string|Theme>|string|Theme  $theme
     */
    public function __construct(
        string|array|Theme $theme,
        private readonly string $defaultGrammar,
    ) {
        parent::__construct($theme);
    }

    protected function detectGrammar(FencedCode $node): Grammar|string
    {
        if (! isset($node->getInfoWords()[0]) || $node->getInfoWords()[0] === '') {
            return $this->defaultGrammar;
        }

        return parent::detectGrammar($node);
    }
}
