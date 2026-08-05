<?php

namespace Yazar\Exporters;

use Illuminate\Support\Facades\Storage;
use Yazar\Contracts\Exporter;
use Yazar\Models\Category;
use Yazar\Models\Document;
use Yazar\Support\Paginator;

class CategoryExporter implements Exporter
{
    /**
     * @param  class-string<Document>  $modelClass
     */
    public function __construct(
        private readonly string $modelClass,
    ) {}

    public function export(): void
    {
        /** @var Category $category */
        foreach (($this->modelClass)::all() as $category) {
            /** @var view-string $viewName */
            $viewName = $category->meta->viewExtends;

            // Ordered to match ContentController::renderCategory(); without it the
            // static pages listed posts in whatever order the database returned.
            $posts = $category->posts()->orderBy('published_at', 'desc')->get();

            foreach (Paginator::for($posts, $category->url)->pages() as $page) {
                $fileHtml = view($viewName, [
                    'category' => $category,
                    'pages' => $page->items,
                    'paginator' => $page,
                ])->render();

                Storage::disk('static_output')->put($page->path(), $fileHtml);
            }
        }
    }
}
