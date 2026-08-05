<?php

namespace Tests\Unit\Support;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Yazar\Support\Paginator;

#[CoversMethod(Paginator::class, 'for')]
#[CoversMethod(Paginator::class, 'has')]
#[CoversMethod(Paginator::class, 'page')]
#[CoversMethod(Paginator::class, 'pages')]
#[CoversMethod(Paginator::class, 'url')]
#[CoversMethod(Paginator::class, 'path')]
#[CoversMethod(Paginator::class, 'window')]
class PaginatorTest extends TestCase
{
    private function paginator(int $itemCount, string $slug = '/', int $perPage = 1): Paginator
    {
        return Paginator::for(new Collection(range(1, $itemCount)), $slug, $perPage);
    }

    #[TestDox('for() counts pages and starts on the first one')]
    public function test_it_starts_on_the_first_page(): void
    {
        $paginator = $this->paginator(3);

        $this->assertSame(3, $paginator->count);
        $this->assertSame(1, $paginator->currentPage);
        $this->assertSame([1], $paginator->items->values()->all());
    }

    #[TestDox('for() reports a single page for an empty listing')]
    public function test_empty_listing_still_has_one_page(): void
    {
        $paginator = Paginator::for(new Collection, '/', 5);

        $this->assertSame(1, $paginator->count);
        $this->assertTrue($paginator->items->isEmpty());
        $this->assertNull($paginator->prevLink);
        $this->assertNull($paginator->nextLink);
    }

    #[TestDox('has() rejects page numbers outside the range')]
    public function test_has_rejects_out_of_range_pages(): void
    {
        $paginator = $this->paginator(3);

        $this->assertFalse($paginator->has(0));
        $this->assertTrue($paginator->has(1));
        $this->assertTrue($paginator->has(3));
        $this->assertFalse($paginator->has(4));
    }

    #[TestDox('page() slices the items belonging to that page')]
    public function test_page_slices_items(): void
    {
        $paginator = Paginator::for(new Collection(range(1, 7)), '/', 3);

        $this->assertSame(3, $paginator->count);
        $this->assertSame([1, 2, 3], $paginator->page(1)->items->values()->all());
        $this->assertSame([4, 5, 6], $paginator->page(2)->items->values()->all());
        $this->assertSame([7], $paginator->page(3)->items->values()->all());
    }

    #[TestDox('pages() yields every page in order with the right items')]
    public function test_pages_yields_every_page(): void
    {
        $pages = iterator_to_array(Paginator::for(new Collection(range(1, 5)), '/', 2)->pages());

        $this->assertCount(3, $pages);
        $this->assertSame([1, 2, 3], array_map(fn (Paginator $page): int => $page->currentPage, $pages));
        $this->assertSame([1, 2], $pages[0]->items->values()->all());
        $this->assertSame([5], $pages[2]->items->values()->all());
    }

    #[TestDox('pages() yields one empty page for an empty listing')]
    public function test_pages_yields_one_page_when_empty(): void
    {
        $pages = iterator_to_array(Paginator::for(new Collection, '/', 2)->pages());

        $this->assertCount(1, $pages);
        $this->assertSame('index.html', $pages[0]->path());
    }

    #[TestDox('url() keeps the root slug for the first page')]
    public function test_urls_for_root_slug(): void
    {
        $paginator = $this->paginator(3);

        $this->assertSame('/', $paginator->url(1));
        $this->assertSame('/2', $paginator->url(2));
        $this->assertSame('/3', $paginator->url(3));
    }

    #[TestDox('url() preserves the internal slash of a nested category slug')]
    public function test_urls_for_nested_category_slug(): void
    {
        $paginator = $this->paginator(3, 'news/tech');

        $this->assertSame('news/tech', $paginator->url(1));
        $this->assertSame('news/tech/2', $paginator->url(2));
    }

    #[TestDox('the previous link from the second page points back to the first')]
    public function test_previous_link_from_second_page(): void
    {
        $this->assertSame('/', $this->paginator(3)->page(2)->prevLink);
        $this->assertSame('news', $this->paginator(3, 'news')->page(2)->prevLink);
    }

    #[TestDox('the first page has no previous link and the last has no next')]
    public function test_edges_have_no_neighbour_links(): void
    {
        $paginator = $this->paginator(3);

        $this->assertNull($paginator->page(1)->prevLink);
        $this->assertSame('/2', $paginator->page(1)->nextLink);
        $this->assertSame('/2', $paginator->page(3)->prevLink);
        $this->assertNull($paginator->page(3)->nextLink);
    }

    #[TestDox('path() writes the first page to index.html and the rest into numbered folders')]
    public function test_output_paths(): void
    {
        $root = $this->paginator(3);

        $this->assertSame('index.html', $root->page(1)->path());
        $this->assertSame('2/index.html', $root->page(2)->path());

        $category = $this->paginator(3, 'news/tech');

        $this->assertSame('news/tech/index.html', $category->page(1)->path());
        $this->assertSame('news/tech/2/index.html', $category->page(2)->path());
    }

    #[TestDox('window() lists every page while the total stays small')]
    public function test_window_lists_all_small_page_counts(): void
    {
        $this->assertSame([1, 2, 3, 4, 5], $this->paginator(5)->window());
    }

    #[TestDox('window() collapses the middle of a long listing into ellipses')]
    public function test_window_collapses_long_listings(): void
    {
        $this->assertSame(
            [1, null, 48, 49, 50, 51, 52, null, 100],
            $this->paginator(100)->page(50)->window(),
        );
    }

    #[TestDox('window() keeps the leading pages expanded near the start')]
    public function test_window_near_the_start(): void
    {
        $this->assertSame([1, 2, 3, 4, null, 100], $this->paginator(100)->page(2)->window());
    }

    #[TestDox('window() keeps the trailing pages expanded near the end')]
    public function test_window_near_the_end(): void
    {
        $this->assertSame([1, null, 97, 98, 99, 100], $this->paginator(100)->page(99)->window());
    }

    #[TestDox('window() never grows with the number of pages')]
    public function test_window_size_is_bounded(): void
    {
        $this->assertLessThanOrEqual(11, count($this->paginator(6000)->page(3000)->window()));
    }
}
