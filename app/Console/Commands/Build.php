<?php

namespace App\Console\Commands;

use App\Enums\DocumentType;
use App\Models\Category;
use App\Models\Document;
use App\Models\Post;
use App\Models\Yazar\Paginator;
use App\Service\DocumentImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;

class Build extends Command
{
    protected $signature = 'build';

    protected $description = 'Generate static build';

    /** @var \Illuminate\Support\Collection<int, Category> */
    protected \Illuminate\Support\Collection $categories;

    public function handle(): int
    {
        Document::truncate();
        $this->buildPosts();
        $this->buildPages();
        $this->buildCategories();
        $this->exportDocuments();
        $this->exportCategories();
        $this->buildFrontPage();
        $this->move();

        $this->info('generating html pages is finish');

        return CommandAlias::SUCCESS;
    }

    protected function exportDocuments(): void
    {
        foreach (Post::all() as $post) {
            $htmlContent = view($post->meta->viewExtends, ['page' => $post])->render();
            Storage::disk('static_output')->put($post->path_for_static_page, $htmlContent);
        }
    }

    protected function exportCategories(): void
    {
        $perPage = max((int) config('content.pagination_per_page', 1), 1);

        foreach (Category::all() as $category) {
            $items = $category->posts;
            $pageCount = max((int) ceil($items->count() / $perPage), 1);

            for ($i = 1; $i <= $pageCount; $i++) {
                $temporarySlug = $i === 1 ? $category->slug.'/index.html' : $category->slug.'/'.$i.'/index.html';
                $pages = $items->forPage($i, $perPage);

                $paginator = new Paginator($pageCount, $category->slug, $i);
                $fileHtml = view($category->meta->viewExtends,
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

    protected function buildCategories(): void
    {
        $importService = new DocumentImportService('categories', DocumentType::Category);
        $importService->import();
    }

    protected function buildPosts(): void
    {
        $importService = new DocumentImportService('posts', DocumentType::Post);
        $importService->import();
    }

    protected function buildPages(): void
    {
        $importService = new DocumentImportService('pages', DocumentType::Page);
        $importService->import();
    }

    protected function buildFrontPage(): void
    {
        $perPage = max((int) config('content.pagination_per_page', 1), 1);
        $collection = Post::orderBy('published_at', 'desc')->get();
        $pageCount = max((int) ceil($collection->count() / $perPage), 1);

        for ($i = 1; $i <= $pageCount; $i++) {
            $slug = $i === 1 ? 'index.html' : '/'.$i;

            $paginator = new Paginator($pageCount, '/', $i);
            $items = $collection->forPage($i, $perPage);
            $html = view('front-page', compact('items', 'paginator'))->render();

            Storage::disk('static_output')->put($slug, $html);
        }
    }

    protected function move(): void
    {
        File::copyDirectory(Storage::disk('static_output')->path('/'), '/mnt/linux-mint/home/seryak/www/www/test.web/public');
        File::copyDirectory(Storage::disk('public')->path('build'), '/mnt/linux-mint/home/seryak/www/www/test.web/public/build');
    }
}
