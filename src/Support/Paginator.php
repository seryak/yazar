<?php

namespace Yazar\Support;

use Generator;
use Illuminate\Support\Collection;

/**
 * One page of a paginated listing, and the entry point to its siblings.
 *
 * Shaped after Laravel's LengthAwarePaginator — it carries both the items of
 * the current page and the navigation around it — with two additions the static
 * build needs: pages() to walk every page in one pass, and path() for the
 * output file.
 *
 * Deliberately *not* an IteratorAggregate: iterating a paginator in Blade is
 * expected to yield items, so exposing pages that way would be a trap.
 *
 * Page links are computed on demand rather than materialised up front. Building
 * the full list in the constructor cost O(pages²) across a build — 6000 pages
 * each holding 6000 strings — and was paid even by templates that only render
 * prev/next.
 */
final class Paginator
{
    /**
     * Items belonging to this page.
     *
     * @var Collection<int, mixed>
     */
    public readonly Collection $items;

    /** Total number of pages, never below 1. */
    public readonly int $count;

    public readonly int $currentPage;

    public readonly ?string $prevLink;

    public readonly ?string $nextLink;

    /**
     * @param  Collection<int, mixed>  $source
     * @param  Collection<int, mixed>|null  $items  already-sliced items, when the caller has them
     */
    private function __construct(
        private readonly Collection $source,
        private readonly string $slug,
        private readonly int $perPage,
        int $currentPage,
        ?Collection $items = null,
    ) {
        $this->count = max((int) ceil($source->count() / $perPage), 1);
        $this->currentPage = $currentPage;
        $this->items = $items ?? $source->forPage($currentPage, $perPage);
        $this->prevLink = $currentPage > 1 ? $this->url($currentPage - 1) : null;
        $this->nextLink = $currentPage < $this->count ? $this->url($currentPage + 1) : null;
    }

    /**
     * @param  Collection<int, mixed>  $items  the full listing, not a single page
     * @param  string  $slug  URL prefix: '/' for the front page, the category slug otherwise
     * @param  int|null  $perPage  defaults to config('yazar.pagination_per_page')
     */
    public static function for(Collection $items, string $slug, ?int $perPage = null): self
    {
        return new self(
            $items,
            $slug,
            max($perPage ?? (int) config('yazar.pagination_per_page', 1), 1),
            1,
        );
    }

    public function has(int $page): bool
    {
        return $page >= 1 && $page <= $this->count;
    }

    public function page(int $number): self
    {
        return new self($this->source, $this->slug, $this->perPage, $number);
    }

    /**
     * Every page in order, as a single chunk() pass. Calling page() in a loop
     * would re-slice the source each time, and array_slice has to walk to the
     * offset — which grows with the page number.
     *
     * @return Generator<int, self>
     */
    public function pages(): Generator
    {
        if ($this->source->isEmpty()) {
            yield $this->page(1);

            return;
        }

        foreach ($this->source->chunk($this->perPage)->values() as $index => $chunk) {
            yield new self($this->source, $this->slug, $this->perPage, $index + 1, $chunk);
        }
    }

    public function url(int $page): string
    {
        return $page === 1 ? $this->slug : rtrim($this->slug, '/').'/'.$page;
    }

    /**
     * Path of this page on the `static_output` disk.
     */
    public function path(): string
    {
        $segments = array_filter([
            trim($this->slug, '/'),
            $this->currentPage === 1 ? '' : (string) $this->currentPage,
            'index.html',
        ], static fn (string $segment): bool => $segment !== '');

        return implode('/', $segments);
    }

    /**
     * Page numbers worth rendering: the first, the last, and a window around
     * the current one. `null` stands for an ellipsis.
     *
     * @return list<int|null>
     */
    public function window(int $onEachSide = 2): array
    {
        if ($this->count <= $onEachSide * 2 + 5) {
            return range(1, $this->count);
        }

        $start = max(2, $this->currentPage - $onEachSide);
        $end = min($this->count - 1, $this->currentPage + $onEachSide);

        $pages = [1];

        if ($start > 2) {
            $pages[] = null;
        }

        array_push($pages, ...range($start, $end));

        if ($end < $this->count - 1) {
            $pages[] = null;
        }

        $pages[] = $this->count;

        return $pages;
    }
}
