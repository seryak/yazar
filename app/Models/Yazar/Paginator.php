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

    public function __construct(int $pageCount, string $slug, int $currentPageNumber)
    {
        $slug = ($slug === '/') ? '' : $slug;
        $this->links = collect([]);
        for ($it = 1; $it <= $pageCount; $it++) {
            $link = $it === 1 ? $slug : $slug . '/' . $it ;
            $this->links->push($link);
        }

        if ($currentPageNumber === 1) {
            $this->prevLink = null;
            $this->nextLink = $pageCount > 1 ? $slug . '/' . $currentPageNumber + 1 : null;
        } else {
            if ($currentPageNumber === 2) {
                $this->prevLink = $slug;
            } else {
                $this->prevLink = $slug . '/' . $currentPageNumber - 1 ;
            }

            $this->nextLink = $currentPageNumber < $pageCount ? $slug . '/' . $currentPageNumber + 1 : null;
        }

        $this->count = $pageCount;
        $this->currentPage = $currentPageNumber;
    }
}
