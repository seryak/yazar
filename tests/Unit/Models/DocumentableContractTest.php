<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yazar\Models\Category;
use Yazar\Models\Document;
use Yazar\Models\Page;
use Yazar\Models\Post;

#[CoversClass(Document::class)]
class DocumentableContractTest extends TestCase
{
    use RefreshDatabase;

    #[TestDox('creating a Post with a mismatched type throws')]
    public function test_creating_post_with_mismatched_type_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Post::create([
            'path' => 'mismatch.md',
            'slug' => 'mismatch',
            'url' => 'blog/mismatch',
            'meta' => [
                'view::extends' => 'page',
                'title' => 'Mismatch',
                'created_at' => 20220506,
            ],
            'content' => '# mismatch',
            'type' => 'category',
            'published_at' => now(),
        ]);
    }

    #[TestDox('creating a Post without an explicit type still works')]
    public function test_creating_post_without_explicit_type_still_works(): void
    {
        $post = Post::create([
            'path' => 'hello.md',
            'slug' => 'hello',
            'url' => 'blog/hello',
            'meta' => [
                'view::extends' => 'page',
                'title' => 'Hello',
                'created_at' => 20220506,
            ],
            'content' => '# hello',
            'published_at' => now(),
        ]);

        $this->assertSame('post', $post->type);
    }

    #[TestDox('creating a Category without an explicit type still works')]
    public function test_creating_category_without_explicit_type_still_works(): void
    {
        $category = Category::create([
            'path' => 'laravel.md',
            'slug' => 'laravel',
            'url' => 'laravel',
            'meta' => [
                'view::extends' => 'category',
                'title' => 'Laravel',
                'created_at' => 20220506,
            ],
            'content' => '# cat',
            'published_at' => now(),
        ]);

        $this->assertSame('category', $category->type);
    }

    #[TestDox('creating a Page without an explicit type still works')]
    public function test_creating_page_without_explicit_type_still_works(): void
    {
        $page = Page::create([
            'path' => 'about.md',
            'slug' => 'about',
            'url' => 'about',
            'meta' => [
                'view::extends' => 'page',
                'title' => 'About',
                'created_at' => 20220506,
            ],
            'content' => '# about',
            'published_at' => now(),
        ]);

        $this->assertSame('page', $page->type);
    }
}
