<?php

namespace Yazar\Exporters;

use Illuminate\Support\Facades\Storage;
use Yazar\Contracts\Exporter;
use Yazar\Models\Document;
use Yazar\Models\Page;

class PageExporter implements Exporter
{
    /**
     * @param  class-string<Document>  $modelClass
     */
    public function __construct(
        private readonly string $modelClass,
    ) {}

    public function export(): void
    {
        /** @var Page $page */
        foreach (($this->modelClass)::all() as $page) {
            /** @var view-string $viewName */
            $viewName = $page->meta->viewExtends;
            $htmlContent = view($viewName, ['page' => $page])->render();
            Storage::disk('static_output')->put($page->path_for_static_page, $htmlContent);
        }
    }
}
