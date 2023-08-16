<?php

namespace App\Console\Commands;

use App\FileCollections\Collection;
use App\Models\Yazar\Category;
use App\Models\Yazar\Page;
use App\Service\CategoryBuilder;
use Illuminate\Console\Command;
use Storage;
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
        $collection->setItems($collection->getItems()->chunk($collection->itemsPerPage));

        /** @var Page $previousPage */
        /** @var Page $currentPage */
        /** @var Page $nextPage */
        $previousPage = $currentPage = $nextPage = null;

        foreach ($collection->getItems() as $subCollection) {
            foreach ($subCollection as $filePath) {
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
            }
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
}
