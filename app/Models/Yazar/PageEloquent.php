<?php

namespace App\Models\Yazar;

use Illuminate\Support\Facades\Storage;

/**
 * @property string $title
 * @property string $slug
 * @property PageDocument $fileDocument
 * @property CategoryEloquent $category
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

    public function render(): void
    {
        $this->fileDocument->render($this->path, $this);
    }


}
