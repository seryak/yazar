<?php

namespace App\Models\Yazar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CategoryEloquent extends Model
{
    use \Sushi\Sushi;

    public function posts()
    {
        return PageEloquent::where('category', $this->slug)->get();
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
        return true;
    }
}
