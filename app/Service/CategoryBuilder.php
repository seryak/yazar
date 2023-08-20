<?php

namespace App\Service;

use App\Models\Yazar\Category;
use App\Models\Yazar\Paginator;
use Illuminate\Support\Facades\Storage;

class CategoryBuilder
{
    public function __construct(public Category $category)
    {
    }

    public function buildFiles(): void
    {
        $items = $this->category->getItems();
        $pageCount = ceil(count($items) / $this->category->itemsPerPage);

        for ($i = 1; $i <= $pageCount; $i++) {
            $temporaryCategory = clone $this->category;
            $temporaryCategory->slug = $i === 1 ? $this->category->slug : $this->category->slug . '/' . $i;

            $paginator = new Paginator();

            $paginator->links = collect([]);
            for ($it = 1; $it <= $pageCount; $it++) {
                $link = $it === 1 ? $this->category->slug : $this->category->slug . '/' . $it;
                $paginator->links->push($link);
            }


            if ($i === 1) {
                $paginator->prevLink = null;
                $paginator->nextLink = $pageCount > 1 ? $this->category->slug . '/' . $i + 1 : null;
            } else {
                if ($i === 2) {
                    $paginator->prevLink = $this->category->slug;
                } else {
                    $paginator->prevLink = $this->category->slug . '/' . $i - 1;
                }

                $paginator->nextLink = $i < $pageCount ? $this->category->slug . '/' . $i + 1 : null;
            }

            $paginator->count = $pageCount;
            $paginator->currentPage = $i;

            $temporaryCategory->paginator = $paginator;

            $part = $items->forPage($i, $this->category->itemsPerPage);
            $temporaryCategory->setItems($part);

            Storage::disk('public')->put($temporaryCategory->getOutputPath(), $temporaryCategory->fileHtml);
        }
    }
}
