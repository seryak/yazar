<?php

namespace App\Console\Commands;

use App\FileCollections\Collection;
use App\Models\Yazar\Category;
use App\Models\Yazar\Page;
use App\Models\Yazar\Paginator;
use App\Service\CategoryBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Webmozart\Assert\Assert;

class Build extends Command
{
    protected $signature = 'build';
    protected $description = 'Generate static build';

    protected array $categories;
    protected Collection $frontPageCollection;


    public function handle(): int
    {
        $contentCollections = config('content')['collections'];
        $this->frontPageCollection = new Collection;

        foreach ($contentCollections as $nameCollection => $collection) {
            $this->validate($collection, $nameCollection);

            $collectionObject = new Collection;
            $collectionObject->setItems($collection['items']);
            $collectionObject->path = $collection['path'];
            $collectionObject->sorting = $collection['sorting'];

            $this->buildHtmlPages($collectionObject);
        }

        $this->buildFrontPage();

        $this->info('generating html pages is finish');
        return CommandAlias::SUCCESS;
    }

    protected function validate(array $collection, string $nameCollection): void
    {
        Assert::keyExists($collection, 'sorting', '"sorting" is required key of collection ' .$nameCollection);
        Assert::boolean($collection['sorting'], '"sorting" key must be is boolean - collection ' .$nameCollection);

        Assert::keyExists($collection, 'path', '"path" is required key of collection ' .$nameCollection);
        Assert::string($collection['path'], '"path" key must be is string - collection ' .$nameCollection);

        Assert::keyExists($collection, 'items', '"items" is required key of collection ' .$nameCollection);
        if (!is_array($collection['items'])) {
            throw new \RuntimeException('"path" key must be is array - collection ' .$nameCollection);
        }
    }

    protected function buildHtmlPages(Collection $collection): void
    {
        /** @var Category[] $categories */
        $this->categories = Category::all();

        /** @var Page $previousPage */
        $previousPage = null;

        $counter = 0;
        foreach ($collection->getItems() as $filePath) {
            $page = new Page($filePath);
            $page->generateSlug($collection->path);

            $page->previousPage = $previousPage;

            if (isset($previousPage)) {
                $previousPage->nextPage = $page;
                $this->addPageToCategory($previousPage);
                $previousPage->render();
                $this->frontPageCollection->addItem($page);
            }

            $previousPage = $page;

            $page->render();

            if( $counter === $collection->getItems()->count() - 1) {
                $this->frontPageCollection->addItem($page);
            }
            $counter++;
        }

        foreach ($this->categories as $category) {
            $builder = new CategoryBuilder($category);
            $builder->buildFiles();
        }
    }

    protected function addPageToCategory(Page $page): void
    {
        if (isset($page->category, $this->categories[$page->category->slug])) {
            $this->categories[$page->category->slug]->addItem($page);
        }
    }

    protected function buildFrontPage():void
    {
        $this->frontPageCollection->sortItems('createdAt', true);
        $pageCount = ceil($this->frontPageCollection->getItems()->count() / $this->frontPageCollection->itemsPerPage);

        for ($i = 1; $i <= $pageCount; $i++) {
            $slug = $i === 1 ? 'index.html' : '/' . $i . '/index.html';

            $paginator = new Paginator();

            $paginator->links = collect([]);
            for ($it = 1; $it <= $pageCount; $it++) {
                $link = $it === 1 ? 'index.html' : '/' . $it . '/index.html';
                $paginator->links->push($link);
            }

            if ($i === 1) {
                $paginator->prevLink = null;
                $paginator->nextLink = $pageCount > 1 ? '/' . $i + 1 . '/index.html' : null;
            } else {
                if ($i === 2) {
                    $paginator->prevLink = 'index.html';
                } else {
                    $paginator->prevLink = '/' . $i - 1 . '/index.html';
                }

                $paginator->nextLink = $i < $pageCount ? '/' . $i + 1 . '/index.html' : null;
            }

            $paginator->count = $pageCount;
            $paginator->currentPage = $i;

            $items = $this->frontPageCollection->getItems()->forPage($i, $this->frontPageCollection->itemsPerPage);
            $html = view('front-page', compact('items', 'paginator'))->render();

            Storage::disk('public')->put(config('content.output_directory').'/'. $slug, $html);
        }
    }
}
