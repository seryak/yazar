<?php

namespace App\Service;

use App\Models\Yazar\CategoryEloquent;
use App\Models\Yazar\Paginator;
use Illuminate\Support\Facades\Storage;

class CategoryBuilder
{
    public function __construct(public CategoryEloquent $category)
    {
    }

    public function buildFiles(): void
    {
        $items = $this->category->pages;
        $pageCount = ceil(count($items) / 1);

        for ($i = 1; $i <= $pageCount; $i++) {
            $temporaryCategory = clone $this->category;
            $temporaryCategory->slug = $i === 1 ? $this->category->slug .'/index.html' : $this->category->slug . '/' . $i .'/index.html';

            $pages = $items->forPage($i, 1);

            $paginator = new Paginator($pageCount, $this->category->slug, $i);
            $fileHtml = view($temporaryCategory->fileDocument->view,
                             [
                                 'category' => $temporaryCategory,
                                 'pages' => $pages,
                                 'paginator' => $paginator
                             ]
            )->render();

            Storage::disk('public')->put(config('content.output_directory') . '/' . $temporaryCategory->slug, $fileHtml);
        }
    }
}
