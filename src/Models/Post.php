<?php

namespace Yazar\Models;

use Yazar\Contracts\Exporter;
use Yazar\Exporters\PostExporter;

class Post extends Document
{
    public static function documentType(): string
    {
        return 'post';
    }

    public static function documentsPath(): string
    {
        return 'posts';
    }

    /**
     * @return class-string<Exporter>
     */
    public static function exporterClass(): string
    {
        return PostExporter::class;
    }

    public static function permalink(): string
    {
        return '/blog/:slug';
    }
}
