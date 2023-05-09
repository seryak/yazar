<?php

namespace App\Models\Yazar;

use App\Service\MarkdownParser;
use Carbon\Carbon;

class Page
{
    public string $view;
    public string $path;
    public string $fileName;
    public string $fileHtml;

    public string $title;
    public Carbon $createdAt;

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
        $this->fileHtml = view($this->view, ['page' => $this])->render();
    }
}
