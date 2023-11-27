<?php

namespace App\Console\Commands;

use App\FileCollections\Collection;
use App\Models\Yazar\Category;
use App\Models\Yazar\CategoryEloquent;
use App\Models\Yazar\PageDocument;
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
        $files = PageEloquent::all();
//        dd($files->toArray());
//        $categories = CategoryEloquent::all();
//        dd(CategoryEloquent::find(2));
//        dd(CategoryEloquent::find(2)->pages);
//        dd(PageEloquent::where('category', CategoryEloquent::find(2)->slug)->first()->posts());
        $this->buildPages($files);
        $this->buildCategories();
//        dd(PageEloquent::query()->where('category', 'jetbrains')->count());

//        $contentCollections = config('content')['collections'];
//        $this->frontPageCollection = new Collection;

//        foreach ($contentCollections as $nameCollection => $collection) {
//            $this->validate($collection, $nameCollection);
//
//            $collectionObject = new Collection;
//            $collectionObject->setItems($collection['items']);
//            $collectionObject->path = $collection['path'];
//            $collectionObject->sorting = $collection['sorting'];
//
//            $this->buildHtmlPages($collectionObject);
//        }

//        $this->buildFrontPage();
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

    protected function buildHtmlPages(Collection $collection): void
    {
        /** @var PageDocument $previousPage */
        $previousPage = null;

        $counter = 0;
        foreach ($collection->getItems() as $filePath) {
            $page = new PageDocument($filePath);
            $page->generateSlug($collection->path);

            $page->previousPage = $previousPage;

            if (isset($previousPage)) {
                $previousPage->nextPage = $page;
                $previousPage->render();
                $this->frontPageCollection->addItem($page);
            }

            $previousPage = $page;

            $page->render();

            if ($counter === $collection->getItems()->count() - 1) {
                $this->frontPageCollection->addItem($page);
            }
            $counter++;
        }
    }

    protected function buildPages(\Illuminate\Database\Eloquent\Collection $collection): void
    {
        /** @var PageEloquent $previousPage */
        /** @var PageEloquent $nextPage */
        /** @var PageEloquent $page */

        $collection = $collection->getIterator();

        foreach ($collection as $page) {
            $page->previousPage = prev($collection);
            $page->nextPage = next($collection);

            $page->render();

        }

//        foreach ($this->categories as $category) {
//            $builder = new CategoryBuilder($category);
//            $builder->buildFiles();
//        }
    }

    protected function buildFrontPage(): void
    {
        $this->frontPageCollection->sortItems('createdAt', true);
        $pageCount = ceil($this->frontPageCollection->getItems()->count() / $this->frontPageCollection->itemsPerPage);

        for ($i = 1; $i <= $pageCount; $i++) {
            $slug = $i === 1 ? 'index.html' : '/' . $i;

            $paginator = new Paginator($pageCount, '/', $i);
            $items = $this->frontPageCollection->getItems()->forPage($i, $this->frontPageCollection->itemsPerPage);
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
