<?php

namespace App\Models\Yazar;

use App\Service\MarkdownParser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Category extends FileDocument
{
    public string $description;
    public ?string $slug;
    public ?Paginator $paginator;

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
        $this->slug = isset($parser->options['slug']) ? $parser->options['slug'] : null;
        $this->description = $parser->options['description'];
        $this->fileName = $path;
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

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'view' => $this->view,
            'created_at' => $this->createdAt,
            'fileName' => $this->fileName,
            'filePath' => $this->fileName,
            'slug' => \Illuminate\Support\Str::replace('.md', '', $this->fileName),
        ];
    }
}
