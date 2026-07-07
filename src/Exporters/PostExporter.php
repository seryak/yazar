<?php

namespace Yazar\Exporters;

use Illuminate\Support\Facades\Storage;
use Yazar\Contracts\Documentable;
use Yazar\Contracts\Exporter;
use Yazar\Models\Document;
use Yazar\Models\Post;

class PostExporter implements Exporter
{
    /**
     * @param  class-string<Document&Documentable>  $modelClass
     */
    public function __construct(
        private readonly string $modelClass,
    ) {}

    public function export(): void
    {
        /** @var Post $post */
        foreach (($this->modelClass)::all() as $post) {
            /** @var view-string $viewName */
            $viewName = $post->meta->viewExtends;
            $htmlContent = view($viewName, ['page' => $post])->render();
            Storage::disk('static_output')->put($post->path_for_static_page, $htmlContent);
        }
    }
}
