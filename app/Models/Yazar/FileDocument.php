<?php

namespace App\Models\Yazar;

use App\Service\MarkdownParser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class FileDocument
{
    public string $view;
    public string $fileName;
    public string $fileHtml;
    public string $title;
    public Carbon $createdAt;
    protected ?string $slug;
    public array $meta;
    public string $htmlContent;

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
        $filenamePath = config('content.use_html_suffix') ? $this->slug. '.html' : $this->slug.'/index.html';
        return config('content.output_directory').'/'. $filenamePath;
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

    public function getSlug(): string
    {
        return config('content.use_html_suffix') ? $this->slug : Str::remove('/index.html', $this->slug);
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'view' => $this->view,
            'createdAt' => $this->createdAt,
            'fileName' => $this->fileName,
            'htmlContent' => $this->htmlContent,
        ];
    }
}
