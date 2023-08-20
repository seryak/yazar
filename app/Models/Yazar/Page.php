<?php

namespace App\Models\Yazar;

use App\Service\MarkdownParser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Page
{
    public string $view;
    public string $fileName;
    public string $fileHtml;

    public string $title;
    public Carbon $createdAt;

    public string $slug;

    public array $meta;
    public string $htmlContent;
    public ?Category $category;

    public ?Page $nextPage = null;
    public ?Page $previousPage = null;

    /**
     * @test {@see Tests\Unit\App\Models\Page\Constructor)}
     */
    public function __construct(string $path)
    {
        $parser = new MarkdownParser;

        $file = file_get_contents($path);

        $parser->parse($file);

        $this->view = $parser->options['view::extends'];
        $this->title = $parser->options['title'];
        $this->createdAt = Carbon::parse($parser->options['created_at']);
        $this->htmlContent = $parser->content;
        $this->fileName = explode('.', basename($path))[0];
        if (isset($parser->options['category'])) {
            $this->category = new Category($parser->options['category'].'.md');
        }
    }

    public function getOutputPath(): string
    {
        return config('content.output_directory').'/'. $this->slug;
    }

    /**
     * Generate slug from pattern
     * @param string $pattern
     * @return void
     */
    public function generateSlug(string $pattern): void
    {
        $str = $pattern;
        $variables = Str::of($str)->matchAll('/\\{(.*?)\\}/');
        foreach ($variables as $var) {
            $str = Str::of($str)->replace('{'.$var.'}', $this->$var);
        }

        $this->slug = config('content.use_html_suffix') ? $str. '.html' : $str.'/index.html';
    }

    public function render(): void
    {
        $this->fileHtml = view($this->view, ['page' => $this])->render();
        Storage::disk('public')->put($this->getOutputPath(), $this->fileHtml);
    }
}
