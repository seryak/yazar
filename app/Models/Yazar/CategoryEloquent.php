<?php

namespace App\Models\Yazar;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $title
 * @property Collection $pages
 */
class CategoryEloquent extends DocumentEloquent
{
    use \Sushi\Sushi;

    public function getPagesAttribute()
    {
        return PageEloquent::where('categoryName', $this->slug)->get();
    }

    public function getFileDocumentAttribute(): Category
    {
        return new Category($this->filePath);
    }

    public function getRows(): array
    {
        $categories = [];
        $files = Storage::disk('categories')->files();
        foreach ($files as $filePath) {

            $category = new Category($filePath);
            $categories[] = $category->toArray();
        }

        return $categories;
    }

    protected function sushiShouldCache(): bool
    {
        return false;
    }
}
