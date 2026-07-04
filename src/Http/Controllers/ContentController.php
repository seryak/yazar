<?php

namespace Yazar\Http\Controllers;

use Illuminate\Contracts\View\View;
use Yazar\Models\Category;
use Yazar\Models\Document;
use Yazar\Support\Paginator;

class ContentController extends Controller
{
    public function show(string $slug): View
    {
        foreach (config('yazar.content_types', []) as $contentType) {
            $modelClass = $contentType['model'];
            $document = $modelClass::where('slug', $slug)->first();

            if ($document === null) {
                continue;
            }

            if ($contentType['type'] === Category::documentType()) {
                if (! $document instanceof Category) {
                    continue;
                }

                return $this->renderCategory($document, 1);
            }

            return $this->renderDocument($document);
        }

        abort(404);
    }

    public function showCategoryPage(string $slug, int $pageNumber): View
    {
        $categoryModel = config('yazar.content_types.categories.model');
        $category = $categoryModel::where('slug', $slug)->firstOrFail();

        if (! $category instanceof Category) {
            abort(404);
        }

        return $this->renderCategory($category, $pageNumber);
    }

    public function renderMainPage(int $pageNumber = 1): View
    {
        $postModel = config('yazar.content_types.posts.model');
        $perPage = max((int) config('yazar.pagination_per_page', 1), 1);
        $collection = $postModel::orderBy('published_at', 'desc')->get();

        $totalItems = $collection->count();
        $pageCount = max((int) ceil($totalItems / $perPage), 1);

        if ($pageNumber < 1 || $pageNumber > $pageCount) {
            abort(404);
        }

        $items = $collection->forPage($pageNumber, $perPage);
        $paginator = new Paginator($pageCount, '/', $pageNumber);

        /** @var view-string $viewName */
        $viewName = config('yazar.front_page_view', 'front-page');

        return view($viewName, compact('items', 'paginator'));
    }

    private function renderDocument(Document $page): View
    {
        $previousPage = $page::where('published_at', '<', $page->published_at)
            ->orderBy('published_at', 'desc')
            ->first();

        $nextPage = $page::where('published_at', '>', $page->published_at)
            ->orderBy('published_at')
            ->first();

        $page->setAttribute('previousPage', $previousPage);
        $page->setAttribute('nextPage', $nextPage);

        $categorySlug = $page->meta->category;
        $categoryModel = config('yazar.content_types.categories.model');
        $category = is_scalar($categorySlug)
            ? $categoryModel::where('slug', (string) $categorySlug)->first()
            : null;

        $page->setAttribute('category', $category);

        $viewName = $page->meta->viewExtends;
        if (! view()->exists($viewName)) {
            abort(404);
        }

        return view($viewName, compact('page'));
    }

    private function renderCategory(Category $category, int $pageNumber): View
    {
        $perPage = max((int) config('yazar.pagination_per_page', 1), 1);
        $items = $category->posts()->orderBy('published_at', 'desc')->get();
        $totalItems = $items->count();
        $pageCount = max((int) ceil($totalItems / $perPage), 1);

        if ($pageNumber < 1 || $pageNumber > $pageCount) {
            abort(404);
        }

        $pages = $items->forPage($pageNumber, $perPage);
        $paginator = new Paginator($pageCount, $category->slug, $pageNumber);
        $viewName = $category->meta->viewExtends;
        if (! view()->exists($viewName)) {
            abort(404);
        }

        return view($viewName,
            [
                'category' => $category,
                'pages' => $pages,
                'paginator' => $paginator,
            ]
        );
    }
}
