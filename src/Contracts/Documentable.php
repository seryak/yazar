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

    /**
     * @return class-string<Exporter>
     */
    public static function exporterClass(): string;

    /**
     * Template for this model's canonical url, with a leading slash and
     * `:token` placeholders (only `:slug` is supported at the moment).
     * Leading/trailing slashes in the resolved url are trimmed by the
     * resolver, not by this method.
     */
    public static function permalink(): string;
}
