<?php

namespace App\Models\Yazar;

use App\Service\MarkdownParser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\CommonMark\Exception\CommonMarkException;

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
        $file = Storage::disk('documents')->get($path);

        try {
            $parser->parse($file);
        } catch (CommonMarkException $e) {
            throw new \RuntimeException($e->getMessage());
        }
        $this->view = $parser->options['view::extends'];
        $this->title = $parser->options['title'];
        $this->slug = isset($parser->options['slug']) ? $parser->options['slug'] : null;
        $this->createdAt = Carbon::parse($parser->options['created_at']);
        $this->category = isset($parser->options['category']) ? $parser->options['category'] : null;
        $this->htmlContent = $parser->content;
        $this->fileName = explode('.', basename($path))[0];
        $this->filePath = $path;
        $this->meta = $parser->options;
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
            'meta' => $this->meta,
        ];
    }
}
