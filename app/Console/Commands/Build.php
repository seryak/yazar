<?php

namespace App\Console\Commands;

use App\FileCollections\Collection;
use App\Models\Yazar\CategoryEloquent;
use App\Models\Yazar\PageEloquent;
use App\Models\Yazar\Paginator;
use App\Service\CategoryBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;

class Build extends Command
{
    protected $signature = 'build';
    protected $description = 'Generate static build';

    protected \Illuminate\Support\Collection $categories;
    protected Collection $frontPageCollection;


    public function handle(): int
    {
        $this->buildPages();
        $this->buildCategories();
        $this->buildFrontPage();

        $this->move();

        $this->info('generating html pages is finish');
        return CommandAlias::SUCCESS;
    }

    protected function buildCategories(): void
    {
        /** @var CategoryEloquent[] $categories */
        $this->categories = CategoryEloquent::all();

        foreach ($this->categories as $category) {
            $builder = new CategoryBuilder($category);
            $builder->buildFiles();
        }
    }

    protected function buildPages(): void
    {
        /** @var PageEloquent $previousPage */
        /** @var PageEloquent $nextPage */
        /** @var PageEloquent $page */

        $collection = PageEloquent::all()->getIterator();

        foreach ($collection as $i => $page) {
            $page->previousPage = isset($collection[$i-1]) ? $collection[$i-1] : null;
            $page->nextPage = isset($collection[$i+1]) ? $collection[$i+1] : null;
            $page->render();
        }
    }

    protected function buildFrontPage(): void
    {
        $collection = PageEloquent::orderBy('createdAt', 'desc')->get();
        $pageCount = ceil($collection->count() / 1);

        for ($i = 1; $i <= $pageCount; $i++) {
            $slug = $i === 1 ? 'index.html' : '/' . $i;

            $paginator = new Paginator($pageCount, '/', $i);
            $items = $collection->forPage($i, 1);
            $html = view('front-page', compact('items', 'paginator'))->render();

            Storage::disk('public')->put(config('content.output_directory') . '/' . $slug, $html);
        }
    }

    protected function move(): void
    {
        File::copyDirectory(Storage::disk('public')->path('static-content'), '/var/www/html/test.web/public');
        File::copyDirectory(Storage::disk('public')->path('build'), '/var/www/html/test.web/public/build');
    }
}
