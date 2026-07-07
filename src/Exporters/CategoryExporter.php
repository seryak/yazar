<?php

namespace Yazar\Exporters;

use Illuminate\Support\Facades\Storage;
use Yazar\Contracts\Documentable;
use Yazar\Contracts\Exporter;
use Yazar\Models\Category;
use Yazar\Models\Document;
use Yazar\Support\Paginator;

class CategoryExporter implements Exporter
{
    /**
     * @param  class-string<Document&Documentable>  $modelClass
     */
    public function __construct(
        private readonly string $modelClass,
    ) {}

    public function export(): void
    {
        $perPage = max((int) config('yazar.pagination_per_page', 1), 1);

        /** @var Category $category */
        foreach (($this->modelClass)::all() as $category) {
            $items = $category->posts;
            $pageCount = max((int) ceil($items->count() / $perPage), 1);

            for ($i = 1; $i <= $pageCount; $i++) {
                $temporarySlug = $i === 1 ? $category->slug.'/index.html' : $category->slug.'/'.$i.'/index.html';
                $pages = $items->forPage($i, $perPage);

                $paginator = new Paginator($pageCount, $category->slug, $i);
                /** @var view-string $viewName */
                $viewName = $category->meta->viewExtends;
                $fileHtml = view($viewName,
                    [
                        'category' => $category,
                        'pages' => $pages,
                        'paginator' => $paginator,
                    ]
                )->render();

                Storage::disk('static_output')->put($temporarySlug, $fileHtml);
            }
        }
    }
}
