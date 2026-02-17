<?php

namespace App\Enums;

enum DocumentType: string
{
    case Post = 'post';
    case Category = 'category';
    case Page = 'page';
}
