<?php

namespace Yazar\Http\Controllers;

use Illuminate\Contracts\View\View;
use Yazar\Models\Category;
use Yazar\Models\Document;
use Yazar\Support\Paginator;

class ContentController extends Controller
{
    public function show(string $url): View
    {
        foreach (config('yazar.content_types', []) as $modelClass) {
            $document = $modelClass::where('url', $url)->first();

            if ($document === null) {
                continue;
            }

            if ($modelClass::documentType() === Category::documentType()) {
                if (! $document instanceof Category) {
                    continue;
                }

                return $this->renderCategory($document, 1);
            }

            return $this->renderDocument($document);
        }

        abort(404);
    }

    public function showCategoryPage(string $url, int $pageNumber): View
    {
        $categoryModel = config('yazar.content_types.categories');
        $category = $categoryModel::where('url', $url)->firstOrFail();

        if (! $category instanceof Category) {
            abort(404);
        }

        return $this->renderCategory($category, $pageNumber);
    }

    public function renderMainPage(int $pageNumber = 1): View
    {
        $postModel = config('yazar.content_types.posts');
        $paginator = Paginator::for($postModel::orderBy('published_at', 'desc')->get(), '/');

        if (! $paginator->has($pageNumber)) {
            abort(404);
        }

        $page = $paginator->page($pageNumber);

        /** @var view-string $viewName */
        $viewName = config('yazar.front_page_view', 'front-page');

        return view($viewName, ['items' => $page->items, 'paginator' => $page]);
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
        $categoryModel = config('yazar.content_types.categories');
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
        $paginator = Paginator::for(
            $category->posts()->orderBy('published_at', 'desc')->get(),
            $category->url,
        );

        if (! $paginator->has($pageNumber)) {
            abort(404);
        }

        $viewName = $category->meta->viewExtends;
        if (! view()->exists($viewName)) {
            abort(404);
        }

        $page = $paginator->page($pageNumber);

        return view($viewName, [
            'category' => $category,
            'pages' => $page->items,
            'paginator' => $page,
        ]);
    }
}
