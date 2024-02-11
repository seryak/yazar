<?php

namespace App\Models\Yazar;

use Illuminate\Support\Facades\Storage;

class PageDocument extends FileDocument
{
    public function render(string $path, PageEloquent $page): void
    {
        $this->fileHtml = view($this->view, ['page' => $page])->render();
        Storage::disk('public')->put(config('content.output_directory') . '/' . $path, $this->fileHtml);
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
