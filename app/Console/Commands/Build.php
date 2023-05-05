<?php

namespace App\Console\Commands;

use App\Models\Yazar\Page;
use App\Service\MarkdownParser;
use Illuminate\Console\Command;
use Storage;
use Webmozart\Assert\Assert;

class Build extends Command
{
    protected $signature = 'build';
    protected $description = 'Generate static build';


    public function handle()
    {
        $contentCollections = config('content');
        foreach ($contentCollections as $nameCollection => $collection) {
            $this->validate($collection, $nameCollection);
            $this->buildHtmlPages($collection);
        }

        $this->info('generating html pages is finish');
        return Command::SUCCESS;
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

    protected function buildHtmlPages(array $collection) {
        foreach ($collection['items'] as $filepath) {
            $page = new Page($filepath);
            Storage::disk('public')->put(env('OUTPUT_DIRECTORY').'/'.$page->fileName.'.html', $page->fileHtml);
        }
    }
}
