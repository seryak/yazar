<?php

namespace Yazar\Models;

use Yazar\Contracts\Documentable;

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
}
