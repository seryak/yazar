<?php

namespace Tests\Unit\Support;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Yazar\Support\Paginator;

#[CoversMethod(Paginator::class, '__construct')]
class PaginatorTest extends TestCase
{
    /**
     * {@see Paginator::__construct()}
     */
    public function test_first_page_link_for_root_slug_is_root(): void
    {
        $paginator = new Paginator(3, '/', 1);

        $this->assertSame('/', $paginator->links->first());
        $this->assertSame('/2', $paginator->links->get(1));
        $this->assertSame('/3', $paginator->links->get(2));
    }

    /**
     * {@see Paginator::__construct()}
     */
    public function test_previous_link_from_second_page_of_root_slug_points_to_root(): void
    {
        $paginator = new Paginator(3, '/', 2);

        $this->assertSame('/', $paginator->prevLink);
        $this->assertSame('/3', $paginator->nextLink);
    }

    /**
     * {@see Paginator::__construct()}
     */
    public function test_first_page_of_root_slug_has_no_previous_link(): void
    {
        $paginator = new Paginator(3, '/', 1);

        $this->assertNull($paginator->prevLink);
        $this->assertSame('/2', $paginator->nextLink);
    }

    /**
     * {@see Paginator::__construct()}
     */
    public function test_last_page_of_root_slug_has_no_next_link(): void
    {
        $paginator = new Paginator(3, '/', 3);

        $this->assertSame('/2', $paginator->prevLink);
        $this->assertNull($paginator->nextLink);
    }

    /**
     * {@see Paginator::__construct()}
     */
    public function test_flat_category_slug_links(): void
    {
        $paginator = new Paginator(3, 'news', 1);

        $this->assertSame('news', $paginator->links->first());
        $this->assertSame('news/2', $paginator->links->get(1));
        $this->assertNull($paginator->prevLink);
    }

    /**
     * {@see Paginator::__construct()}
     */
    public function test_previous_link_from_second_page_of_flat_category_slug(): void
    {
        $paginator = new Paginator(3, 'news', 2);

        $this->assertSame('news', $paginator->prevLink);
        $this->assertSame('news/3', $paginator->nextLink);
    }

    /**
     * {@see Paginator::__construct()}
     */
    public function test_last_page_of_flat_category_slug_has_no_next_link(): void
    {
        $paginator = new Paginator(3, 'news', 3);

        $this->assertNull($paginator->nextLink);
    }

    /**
     * {@see Paginator::__construct()}
     */
    public function test_nested_category_slug_preserves_internal_slash(): void
    {
        $paginator = new Paginator(3, 'news/tech', 2);

        $this->assertSame('news/tech', $paginator->links->first());
        $this->assertSame('news/tech/2', $paginator->links->get(1));
        $this->assertSame('news/tech', $paginator->prevLink);
        $this->assertSame('news/tech/3', $paginator->nextLink);
    }
}
