<?php

namespace Yazar\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Yazar\Documents\DocumentImportService;
use Yazar\Models\Category;
use Yazar\Models\Document;
use Yazar\Support\Paginator;

class BuildCommand extends Command
{
    protected $signature = 'build';

    protected $description = 'Generate static build';

    public function handle(): int
    {
        Document::truncate();
        DocumentImportService::importAllConfiguredModels();
        $this->exportDocuments();
        $this->exportCategories();
        $this->buildFrontPage();
        $this->move();

        $this->info('generating html pages is finish');

        return CommandAlias::SUCCESS;
    }

    protected function exportDocuments(): void
    {
        $postModel = config('yazar.content_types.posts');

        foreach ($postModel::all() as $post) {
            /** @var view-string $viewName */
            $viewName = $post->meta->viewExtends;
            $htmlContent = view($viewName, ['page' => $post])->render();
            Storage::disk('static_output')->put($post->path_for_static_page, $htmlContent);
        }
    }

    protected function exportCategories(): void
    {
        $categoryModel = config('yazar.content_types.categories');
        $perPage = max((int) config('yazar.pagination_per_page', 1), 1);

        /** @var Category $category */
        foreach ($categoryModel::all() as $category) {
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

    protected function buildFrontPage(): void
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

    protected function move(): void
    {
        $deployTarget = config('yazar.deploy_target');

        if ($deployTarget === null) {
            return;
        }

        File::copyDirectory(Storage::disk('static_output')->path('/'), $deployTarget);
        File::copyDirectory(Storage::disk('public')->path('build'), $deployTarget.'/build');
    }
}
