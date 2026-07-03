<?php

namespace Yazar\Enums;

enum DocumentType: string
{
    case Post = 'post';
    case Category = 'category';
    case Page = 'page';

    public function modelClass(): string
    {
        foreach (config('yazar.content_types', []) as $contentType) {
            if ($contentType['type'] === $this) {
                return $contentType['model'];
            }
        }

        throw new \RuntimeException("No content type configured for '{$this->value}'.");
    }
}
