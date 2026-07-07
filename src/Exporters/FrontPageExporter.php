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
        $perPage = max((int) config('yazar.pagination_per_page', 1), 1);
        $collection = $postModel::orderBy('published_at', 'desc')->get();
        $pageCount = max((int) ceil($collection->count() / $perPage), 1);

        for ($i = 1; $i <= $pageCount; $i++) {
            $slug = $i === 1 ? 'index.html' : '/'.$i;

            $paginator = new Paginator($pageCount, '/', $i);
            $items = $collection->forPage($i, $perPage);
            /** @var view-string $viewName */
            $viewName = config('yazar.front_page_view', 'front-page');
            $html = view($viewName, compact('items', 'paginator'))->render();

            Storage::disk('static_output')->put($slug, $html);
        }
    }
}
