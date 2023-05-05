<?php

namespace App\Console\Commands;

use App\Models\Yazar\Page;
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
            $this->buildHtmlPages($collection);
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

    protected function buildHtmlPages(array $collection): void
    {
        foreach ($collection['items'] as $filepath) {
            $page = new Page($filepath);
            Storage::disk('public')->put($this->getOutputPath($page), $page->fileHtml);
        }
    }

    protected function getOutputPath(Page $page): string
    {
        $filenamePath = config('content.use_html_suffix') ? $page->fileName. '.html' : $page->fileName.'/index.html';
        return config('content.output_directory').'/'. $filenamePath;
    }
}
