<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Storage;

/**
 * Generates a synthetic markdown corpus on the `content` disk for the private
 * yazar:benchmark-build command. Not part of the seryak/yazar package — lives
 * only inside this repository's harness/ (see bin/harness-init.sh).
 */
class SyntheticContentGenerator
{
    /**
     * Generates $count posts (the content type that drives import/export/front_page
     * cost) spread across a fixed 5 categories. Pages are skipped entirely — the
     * benchmark focuses on the posts/categories pipeline, which dominates realistic
     * import/export cost; adding pages would inflate import cost without exercising
     * a materially different code path in the profiled stages.
     */
    public function generate(int $count): void
    {
        $categoryCount = 5;

        for ($i = 0; $i < $categoryCount; $i++) {
            $this->putDocument("categories/category-{$i}.md", 'category', "Category {$i}");
        }

        for ($i = 0; $i < $count; $i++) {
            $category = $i % $categoryCount;
            $this->putDocument("posts/post-{$i}.md", 'page', "Post {$i}", "category-{$category}");
        }
    }

    private function putDocument(string $path, string $viewExtends, string $title, ?string $category = null): void
    {
        $frontMatter = [
            "view::extends: {$viewExtends}",
            "title: {$title}",
            'created_at: "2024-01-01"',
        ];

        if ($category !== null) {
            $frontMatter[] = "category: {$category}";
        }

        $document = "---\n".implode("\n", $frontMatter)."\n---\n# {$title}\n\nSynthetic content for build benchmarking.\n";

        Storage::disk('content')->put($path, $document);
    }
}
