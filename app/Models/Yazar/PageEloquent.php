<?php

namespace App\Models\Yazar;

use App\FileCollections\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageEloquent extends Model
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

    public function category()
    {
        return CategoryEloquent::where('slug', $this->category)->first();
    }

    protected function sushiShouldCache(): bool
    {
        return true;
    }
}
