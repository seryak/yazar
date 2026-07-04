<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Yazar\Documents\DocumentImportService;
use Yazar\Models\Category;
use Yazar\Models\Document;
use Yazar\Models\Page;
use Yazar\Models\Post;

class DynamicContentRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_page_renders_imported_posts(): void
    {
        Post::create([
            'path' => 'hello.md',
            'slug' => 'hello',
            'meta' => [
                'view::extends' => 'page',
                'title' => 'Hello post',
                'created_at' => 20220506,
            ],
            'content' => '# hello',
            'published_at' => now(),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Recent posts');
        $response->assertSee('Hello post');
    }

    public function test_document_slug_route_renders_page_or_post_view(): void
    {
        Page::create([
            'path' => 'test.md',
            'slug' => 'test',
            'meta' => [
                'view::extends' => 'page',
                'title' => 'Test page',
                'created_at' => 20220506,
            ],
            'content' => '# test',
            'published_at' => now(),
        ]);

        $response = $this->get('/test');

        $response->assertStatus(200);
        $response->assertSee('Test page');
    }

    public function test_category_route_renders_related_posts_with_pagination(): void
    {
        Category::create([
            'path' => 'laravel.md',
            'slug' => 'laravel',
            'meta' => [
                'view::extends' => 'category',
                'title' => 'Laravel',
                'description' => 'Category description',
                'created_at' => 20220506,
            ],
            'content' => '# cat',
            'published_at' => now()->subDays(3),
        ]);

        Post::create([
            'path' => 'post-one.md',
            'slug' => 'post-one',
            'meta' => [
                'view::extends' => 'page',
                'title' => 'Post one',
                'category' => 'laravel',
                'created_at' => 20220507,
            ],
            'content' => '# post one',
            'published_at' => now()->subDays(2),
        ]);

        Post::create([
            'path' => 'post-two.md',
            'slug' => 'post-two',
            'meta' => [
                'view::extends' => 'page',
                'title' => 'Post two',
                'category' => 'laravel',
                'created_at' => 20220508,
            ],
            'content' => '# post two',
            'published_at' => now()->subDay(),
        ]);

        $firstPage = $this->get('/laravel');
        $firstPage->assertStatus(200);
        $firstPage->assertSee('Laravel');
        $firstPage->assertSee('Post two');

        $secondPage = $this->get('/laravel/2');
        $secondPage->assertStatus(200);
        $secondPage->assertSee('Post one');
    }

    public function test_unknown_slug_and_invalid_pagination_return_404(): void
    {
        $this->get('/missing')->assertStatus(404);

        Category::create([
            'path' => 'single.md',
            'slug' => 'single',
            'meta' => [
                'view::extends' => 'category',
                'title' => 'Single',
                'created_at' => 20220506,
            ],
            'content' => '# single',
            'published_at' => now(),
        ]);

        $this->get('/single/2')->assertStatus(404);
    }

    public function test_numeric_route_uses_front_page_pagination_priority_over_slug(): void
    {
        Post::create([
            'path' => 'first.md',
            'slug' => 'first',
            'meta' => [
                'view::extends' => 'page',
                'title' => 'First post',
                'created_at' => 20220506,
            ],
            'content' => '# first',
            'published_at' => now(),
        ]);

        Post::create([
            'path' => 'second.md',
            'slug' => 'second',
            'meta' => [
                'view::extends' => 'page',
                'title' => 'Second post',
                'created_at' => 20220507,
            ],
            'content' => '# second',
            'published_at' => now()->subDay(),
        ]);

        Page::create([
            'path' => 'numeric-page.md',
            'slug' => '2',
            'meta' => [
                'view::extends' => 'page',
                'title' => 'Numeric slug page',
                'created_at' => 20220508,
            ],
            'content' => '# numeric',
            'published_at' => now()->subDays(2),
        ]);

        $response = $this->get('/2');

        $response->assertStatus(200);
        $response->assertSee('Recent posts');
        $response->assertSee('Second post');
        $response->assertDontSee('Numeric slug page');
    }

    public function test_empty_storage_is_imported_automatically_for_front_page(): void
    {
        Storage::fake('posts');
        Storage::fake('pages');
        Storage::fake('categories');

        Storage::disk('posts')->put('autoload.md', <<<'EOT'
        ---
        view::extends: page
        title: Autoload post
        created_at: 20220506
        ---
        # autoload
        EOT);

        Document::truncate();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Autoload post');
        $this->assertSame(1, Document::where('slug', 'autoload')->count());
    }

    public function test_import_service_contract_stays_valid_for_dynamic_and_static_modes(): void
    {
        Storage::fake('documents');

        Storage::disk('documents')->put('contract.md', <<<'EOT'
        ---
        view::extends: page
        title: Contract title
        created_at: 20220506
        ---
        # contract
        EOT);

        $service = new DocumentImportService('documents', 'page');
        $result = $service->import();

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame([], $result['invalid_documents']);

        $document = Document::where('slug', 'contract')->first();

        $this->assertNotNull($document);
        $this->assertSame('contract.md', $document->path);
        $this->assertSame('page', $document->type);
    }

    public function test_multi_segment_slug_is_resolved_for_page_route(): void
    {
        Page::create([
            'path' => 'tools/jetbrains/test2.md',
            'slug' => 'tools/jetbrains/test2',
            'meta' => [
                'view::extends' => 'page',
                'title' => 'Nested page',
                'created_at' => 20220506,
            ],
            'content' => '# nested page',
            'published_at' => now(),
        ]);

        $response = $this->get('/tools/jetbrains/test2');

        $response->assertStatus(200);
        $response->assertSee('Nested page');
    }
}
