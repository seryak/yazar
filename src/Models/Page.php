<?php

namespace Yazar\Models;

use Yazar\Contracts\Exporter;
use Yazar\Exporters\PageExporter;

class Page extends Document
{
    public static function documentType(): string
    {
        return 'page';
    }

    public static function documentsPath(): string
    {
        return 'pages';
    }

    /**
     * @return class-string<Exporter>
     */
    public static function exporterClass(): string
    {
        return PageExporter::class;
    }
}
