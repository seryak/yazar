<?php

namespace App\Models\Yazar;

use App\FileCollections\Collection;
use App\Service\MarkdownParser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Category extends Collection
{
    public string $view;
    public string $path;
    public string $fileName;
    public string $fileHtml;

    public string $title;
    public string $description;
    public Carbon $createdAt;

    public array $meta;
    public string $htmlContent;

    public string $slug;

    /**
     * @test {@see Tests\Unit\App\Models\Page\Constructor)}
     */
    public function __construct(string $path)
    {
        $parser = new MarkdownParser;

        $file = Storage::disk('categories')->get($path);
        $parser->parse($file);

        $this->items = collect();
        $this->view = $parser->options['view::extends'];
        $this->title = $parser->options['title'];
        $this->createdAt = Carbon::parse($parser->options['created_at']);
        $this->htmlContent = $parser->content;
        $this->slug = $parser->options['slug'];
        $this->description = $parser->options['description'];
        $this->fileName = $path;
        $this->fileHtml = view($this->view, ['category' => $this])->render();
    }

    public function addItem($item): void
    {
        parent::addItem($item);
        $this->fileHtml = view($this->view, ['category' => $this])->render();
    }

    public function setItems(array|\Illuminate\Support\Collection $items): void
    {
        parent::setItems($items);
        $this->fileHtml = view($this->view, ['category' => $this])->render();
    }

    public static function all(): array
    {
        $categories = [];
        $files = Storage::disk('categories')->files();
        foreach ($files as $filePath) {
            $category = new Category($filePath);
            $categories[$category->slug] = $category;
        }

        return $categories;
    }

    public function getOutputPath(): string
    {
        $filenamePath = config('content.use_html_suffix') ? $this->slug. '.html' : $this->slug.'/index.html';
        return config('content.output_directory').'/'. $filenamePath;
    }
}
