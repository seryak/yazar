<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;
use Yazar\Models\Category;
use Yazar\Models\Page;
use Yazar\Models\Post;

class DocumentableContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_declares_its_document_type(): void
    {
        $this->assertSame('post', Post::documentType());
    }

    public function test_category_declares_its_document_type(): void
    {
        $this->assertSame('category', Category::documentType());
    }

    public function test_page_declares_its_document_type(): void
    {
        $this->assertSame('page', Page::documentType());
    }

    public function test_creating_post_with_mismatched_type_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Post::create([
            'path' => 'mismatch.md',
            'slug' => 'mismatch',
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

    public function test_creating_post_without_explicit_type_still_works(): void
    {
        $post = Post::create([
            'path' => 'hello.md',
            'slug' => 'hello',
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

    public function test_creating_category_without_explicit_type_still_works(): void
    {
        $category = Category::create([
            'path' => 'laravel.md',
            'slug' => 'laravel',
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

    public function test_creating_page_without_explicit_type_still_works(): void
    {
        $page = Page::create([
            'path' => 'about.md',
            'slug' => 'about',
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
