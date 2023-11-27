<?php

namespace App\Models\Yazar;

use App\Service\MarkdownParser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageDocument extends FileDocument
{
    /**
     * @test {@see Tests\Unit\App\Models\Page\Constructor)}
     */
    public function __construct(string $path)
    {
        $parser = new MarkdownParser;

        $file = Storage::disk('documents')->get($path);

        $parser->parse($file);

        $this->view = $parser->options['view::extends'];
        $this->title = $parser->options['title'];
        $this->slug = isset($parser->options['slug']) ? $parser->options['slug'] : null;
        $this->createdAt = Carbon::parse($parser->options['created_at']);
        $this->category = $parser->options['category'];
        $this->htmlContent = $parser->content;
        $this->fileName = explode('.', basename($path))[0];
        $this->filePath = $path;
//        if (isset($parser->options['category'])) {
//            $this->category = new Category($parser->options['category'].'.md');
//        }
    }

    public function render(string $slug, PageEloquent $page): void
    {
        $this->fileHtml = view($this->view, ['page' => $page])->render();
        Storage::disk('public')->put(config('content.output_directory') . '/' . $slug, $this->fileHtml);
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

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'view' => $this->view,
            'created_at' => $this->createdAt,
            'fileName' => $this->fileName,
            'filePath' => $this->filePath,
            'htmlContent' => $this->htmlContent,
            'categoryName' => $this->category,
        ];
    }
}
