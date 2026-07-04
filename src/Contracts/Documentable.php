<?php

namespace Yazar\Contracts;

interface Documentable
{
    public static function documentType(): string;

    /**
     * Relative subfolder for this model's markdown files within the shared
     * `content` disk. No leading or trailing slash (e.g. `'posts'`).
     */
    public static function documentsPath(): string;
}
