<?php

namespace Yazar\Models;

use Yazar\Contracts\Documentable;

class Post extends Document implements Documentable
{
    public static function documentType(): string
    {
        return 'post';
    }

    public static function documentsPath(): string
    {
        return 'posts';
    }
}
