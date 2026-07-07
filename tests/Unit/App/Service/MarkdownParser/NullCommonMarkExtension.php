<?php

namespace Tests\Unit\App\Service\MarkdownParser;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

class NullCommonMarkExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void {}
}
