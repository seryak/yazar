<?php

namespace App\Models\Yazar;

use Illuminate\Support\Collection;

class Paginator
{
    public ?string $nextLink;
    public ?string $prevLink;

    public int $count = 1;

    public int $currentPage = 1;

    public Collection $links;
}
