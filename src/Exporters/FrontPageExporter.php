<?php

namespace Yazar\Exporters;

use Illuminate\Support\Facades\Storage;
use Yazar\Contracts\Exporter;
use Yazar\Support\Paginator;

class FrontPageExporter implements Exporter
{
    public function export(): void
    {
        $postModel = config('yazar.content_types.posts');
        /** @var view-string $viewName */
        $viewName = config('yazar.front_page_view', 'front-page');

        $collection = $postModel::orderBy('published_at', 'desc')->get();

        foreach (Paginator::for($collection, '/')->pages() as $page) {
            $html = view($viewName, ['items' => $page->items, 'paginator' => $page])->render();

            Storage::disk('static_output')->put($page->path(), $html);
        }
    }
}
