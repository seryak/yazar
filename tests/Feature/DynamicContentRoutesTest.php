<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yazar\Documents\DocumentImportService;
use Yazar\Models\Category;
use Yazar\Models\Document;
use Yazar\Models\Page;
use Yazar\Models\Post;

class DynamicContentRoutesTest extends TestCase
{
    use RefreshDatabase;

    #[TestDox('the front page renders imported posts')]
    public function test_front_page_renders_imported_posts(): void
    {
        Post::create([
            'path' => 'hello.md',
            'slug' => 'hello',
            'url' => 'blog/hello',
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
        $response->assertSee('Hello post');
    }

    #[TestDox('the document slug route renders the page or post view')]
    public function test_document_slug_route_renders_page_or_post_view(): void
    {
        Page::create([
            'path' => 'test.md',
            'slug' => 'test',
            'url' => 'test',
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

    #[TestDox('a post resolves at its blog-prefixed url, not at the bare slug')]
    public function test_post_resolves_at_blog_prefixed_url_not_bare_slug(): void
    {
        Post::create([
            'path' => 'hello.md',
            'slug' => 'hello',
            'url' => 'blog/hello',
            'meta' => [
                'view::extends' => 'page',
                'title' => 'Hello post',
                'created_at' => 20220506,
            ],
            'content' => '# hello',
            'published_at' => now(),
        ]);

        $this->get('/blog/hello')->assertStatus(200)->assertSee('Hello post');
        $this->get('/hello')->assertStatus(404);
    }

    #[TestDox('the category route renders related posts with pagination')]
    public function test_category_route_renders_related_posts_with_pagination(): void
    {
        Category::create([
            'path' => 'laravel.md',
            'slug' => 'laravel',
            'url' => 'laravel',
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
            'url' => 'blog/post-one',
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
            'url' => 'blog/post-two',
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

    #[TestDox('an unknown slug and invalid pagination page return 404')]
    public function test_unknown_slug_and_invalid_pagination_return_404(): void
    {
        $this->get('/missing')->assertStatus(404);

        Category::create([
            'path' => 'single.md',
            'slug' => 'single',
            'url' => 'single',
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

    #[TestDox('a numeric route prioritizes front page pagination over slug')]
    public function test_numeric_route_uses_front_page_pagination_priority_over_slug(): void
    {
        Post::create([
            'path' => 'first.md',
            'slug' => 'first',
            'url' => 'blog/first',
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
            'url' => 'blog/second',
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
            'url' => '2',
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
        $response->assertSee('Second post');
        $response->assertDontSee('Numeric slug page');
    }

    #[TestDox('content is imported automatically for the front page when storage is empty')]
    public function test_empty_storage_is_imported_automatically_for_front_page(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('posts/autoload.md', <<<'EOT'
        ---
        view::extends: page
        title: Autoload post
        created_at: 20220506
        ---
        # autoload
        EOT);

        DB::table(Document::TABLE)->truncate();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Autoload post');
        $this->assertDatabaseHas(Document::TABLE, ['slug' => 'autoload', 'type' => 'post']);
        $this->assertDatabaseCount(Document::TABLE, 1);
    }

    #[TestDox('the DocumentImportService contract stays valid in dynamic and static modes')]
    public function test_import_service_contract_stays_valid_for_dynamic_and_static_modes(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/contract.md', <<<'EOT'
        ---
        view::extends: page
        title: Contract title
        created_at: 20220506
        ---
        # contract
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame([], $result['invalid_documents']);

        $document = DB::table(Document::TABLE)->where('slug', 'contract')->first();

        $this->assertNotNull($document);
        $this->assertSame('contract.md', $document->path);
        $this->assertSame('page', $document->type);
    }

    #[TestDox('a multi-segment slug is resolved correctly for the page route')]
    public function test_multi_segment_slug_is_resolved_for_page_route(): void
    {
        Page::create([
            'path' => 'tools/jetbrains/test2.md',
            'slug' => 'tools/jetbrains/test2',
            'url' => 'tools/jetbrains/test2',
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
