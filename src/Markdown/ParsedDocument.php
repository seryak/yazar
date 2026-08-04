<?php

namespace Yazar\Markdown;

final readonly class ParsedDocument
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public array $options,
        public string $markdownContent,
    ) {}
}
