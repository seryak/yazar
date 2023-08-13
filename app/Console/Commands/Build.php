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


    public function handle(): int
    {
        $contentCollections = config('content')['collections'];
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
        $categories = Category::all();
        $collection->setItems($collection->getItems()->chunk($collection->itemsPerPage));

        foreach ($collection->getItems() as $subCollection) {
            foreach ($subCollection as $filePath) {
                $page = new Page($filePath);
                $page->generateSlug($collection->path);
                if (isset($page->category, $categories[$page->category->slug])) {
                    $categories[$page->category->slug]->addItem($page);
                }
                Storage::disk('public')->put($page->getOutputPath(), $page->fileHtml);
            }
        }

        foreach ($categories as $category) {
            $builder = new CategoryBuilder($category);
            $builder->buildFiles();
        }
    }
}
