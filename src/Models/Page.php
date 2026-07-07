<?php

namespace Yazar\Models;

use Yazar\Contracts\Documentable;
use Yazar\Contracts\Exporter;
use Yazar\Exporters\NullExporter;

class Page extends Document implements Documentable
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
        return NullExporter::class;
    }
}
