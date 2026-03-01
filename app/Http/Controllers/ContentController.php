<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use App\Models\Page;
use App\Models\Post;
use App\Models\Yazar\Paginator;
use Illuminate\Contracts\View\View;

class ContentController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::where('slug', $slug)->first();
        if ($page instanceof Page) {
            return $this->renderDocument($page);
        }

        $post = Post::where('slug', $slug)->first();
        if ($post instanceof Post) {
            return $this->renderDocument($post);
        }

        $category = Category::where('slug', $slug)->first();
        if ($category instanceof Category) {
            return $this->renderCategory($category, 1);
        }

        abort(404);
    }

    public function showCategoryPage(string $slug, int $pageNumber): View
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        return $this->renderCategory($category, $pageNumber);
    }

    public function renderMainPage(int $pageNumber = 1): View
    {
        $perPage = max((int) config('content.pagination_per_page', 1), 1);
        $collection = Post::orderBy('published_at', 'desc')->get();

        $totalItems = $collection->count();
        $pageCount = max((int) ceil($totalItems / $perPage), 1);

        if ($pageNumber < 1 || $pageNumber > $pageCount) {
            abort(404);
        }

        $items = $collection->forPage($pageNumber, $perPage);
        $paginator = new Paginator($pageCount, '/', $pageNumber);
        return view('front-page', compact('items', 'paginator'));
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
        $category = is_scalar($categorySlug)
            ? Category::where('slug', (string) $categorySlug)->first()
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
        $perPage = max((int) config('content.pagination_per_page', 1), 1);
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

        return view($viewName, compact('category', 'pages', 'paginator'));
    }
}
