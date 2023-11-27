<?php

namespace App\Models\Yazar;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property string $title
 * @property string $slug
 * @property PageDocument $fileDocument
 */
class PageEloquent extends DocumentEloquent
{
    use \Sushi\Sushi;

    public function getRows(): array
    {
        $records = [];
        $files = Storage::disk('documents')->allFiles();

        foreach ($files as $filePath) {
            $records[] = (new PageDocument($filePath))->toArray();
        }

        return $records;
    }

    public function getCategoryAttribute()
    {
        return CategoryEloquent::where('slug', $this->categoryName)->first();
    }

    protected function sushiShouldCache(): bool
    {
        return false;
    }

    public function getFileDocumentAttribute(): PageDocument
    {
        return new PageDocument($this->filePath);
    }

    public function getSlugAttribute(): string
    {
        if (!$this->attributes['slug']) {
            $path = Str::replace('.md', '', $this->filePath);
            $this->attributes['slug'] = config('content.use_html_suffix') ? $path. '.html' : $path.'/index.html';
        }

        return $this->attributes['slug'];
    }

    public function render(): void
    {
        $this->fileDocument->render($this->slug, $this);
    }


}
