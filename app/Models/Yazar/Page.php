<?php

namespace App\Models\Yazar;

use App\Service\MarkdownParser;

class Page
{
    public string $view;
    public string $path;
    public string $fileName;
    public string $fileHtml;

    public string $title;

    public array $meta;
    public string $htmlContent;

    public function __construct(string $path)
    {
        $parser = new MarkdownParser;

        $file = file_get_contents($path);

        $parser->parse($file);

        $this->view = $parser->options['view::extends'];
        $this->title = $parser->options['title'];
        $this->htmlContent = $parser->content;
        $this->fileHtml = view($this->view, ['page' => $this])->render();
        $this->fileName = explode('.', basename($path))[0];
    }
}
