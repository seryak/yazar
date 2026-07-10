<?php

namespace Tests\Unit\App\Service;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversMethod;
use Tests\TestCase;
use Yazar\Documents\DocumentImportService;
use Yazar\Markdown\Extensions\DiskUrlExtension;
use Yazar\Models\Category;
use Yazar\Models\Document;
use Yazar\Models\Page;
use Yazar\Models\Post;

#[CoversMethod(DocumentImportService::class, 'import')]
#[CoversMethod(DocumentImportService::class, 'importAllConfiguredModels')]
class DocumentImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_skips_invalid_documents_and_collects_invalid_paths(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/valid.md', <<<'EOT'
        ---
        view::extends: layout
        title: valid title
        created_at: 2022-05-06
        ---
        # valid
        EOT);

        Storage::disk('content')->put('pages/invalid.md', <<<'EOT'
        ---
        view::extends: layout
        title: invalid title
        ---
        # invalid
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(2, $result['total']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame(['invalid.md'], $result['invalid_documents']);

        $this->assertDatabaseHas('documents', ['path' => 'valid.md']);
        $this->assertDatabaseMissing('documents', ['path' => 'invalid.md']);
    }

    public function test_it_updates_existing_document_by_path_on_reimport(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/same-path.md', <<<'EOT'
        ---
        view::extends: layout
        title: first title
        created_at: 2022-05-06
        ---
        # first
        EOT);

        $service = new DocumentImportService(Page::class);
        $first = $service->import();

        $this->assertSame(1, $first['imported']);

        Storage::disk('content')->put('pages/same-path.md', <<<'EOT'
        ---
        view::extends: layout
        title: second title
        created_at: 2022-05-07
        ---
        # second
        EOT);

        $second = $service->import();

        $this->assertSame(1, $second['imported']);
        $this->assertSame([], $second['invalid_documents']);
        $this->assertSame(1, Document::count());

        $document = Document::firstOrFail();

        $this->assertSame('same-path.md', $document->path);
        $this->assertSame('second title', $document->meta?->title);
        $this->assertSame('# second', $document->content);
    }

    public function test_it_treats_malformed_front_matter_as_invalid(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/broken.md', <<<'EOT'
        ---
        view::extends: [layout
        title: broken
        created_at: 2022-05-06
        ---
        # broken
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(0, $result['imported']);
        $this->assertSame(['broken.md'], $result['invalid_documents']);
        $this->assertDatabaseMissing('documents', ['path' => 'broken.md']);
    }

    public function test_it_treats_document_with_unregistered_view_as_invalid(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/no-view.md', <<<'EOT'
        ---
        view::extends: this-view-does-not-exist
        title: no view
        created_at: 2022-05-06
        ---
        # no view
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(0, $result['imported']);
        $this->assertSame(['no-view.md'], $result['invalid_documents']);
    }

    public function test_it_treats_blank_required_field_as_invalid(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/blank-title.md', <<<'EOT'
        ---
        view::extends: layout
        title: " "
        created_at: 2022-05-06
        ---
        # blank title
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(0, $result['imported']);
        $this->assertSame(['blank-title.md'], $result['invalid_documents']);
    }

    public function test_it_treats_document_with_broken_disk_link_as_invalid_without_failing_the_whole_import(): void
    {
        Storage::fake('content');

        config(['yazar.markdown.extensions' => [DiskUrlExtension::class]]);

        Storage::disk('content')->put('pages/valid.md', <<<'EOT'
        ---
        view::extends: layout
        title: valid title
        created_at: 2022-05-06
        ---
        # valid
        EOT);

        Storage::disk('content')->put('pages/broken-disk-link.md', <<<'EOT'
        ---
        view::extends: layout
        title: broken disk link
        created_at: 2022-05-06
        ---
        ![alt](disk(unregistered)://photos/cat.png)
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(2, $result['total']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame(['broken-disk-link.md'], $result['invalid_documents']);

        $this->assertDatabaseHas('documents', ['path' => 'valid.md']);
        $this->assertDatabaseMissing('documents', ['path' => 'broken-disk-link.md']);
    }

    public function test_it_excludes_files_with_hash_prefix_from_import(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/visible.md', <<<'EOT'
        ---
        view::extends: layout
        title: visible title
        created_at: 2022-05-06
        ---
        # visible
        EOT);

        Storage::disk('content')->put('pages/#draft.md', <<<'EOT'
        ---
        view::extends: layout
        title: draft title
        created_at: 2022-05-06
        ---
        # draft
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame([], $result['invalid_documents']);

        $this->assertDatabaseHas('documents', ['path' => 'visible.md']);
        $this->assertDatabaseMissing('documents', ['path' => '#draft.md']);
    }

    public function test_it_excludes_hash_prefixed_file_regardless_of_subfolder(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('posts/#hidden.md', <<<'EOT'
        ---
        view::extends: layout
        title: hidden title
        created_at: 2022-05-06
        ---
        # hidden
        EOT);

        Storage::disk('content')->put('posts/not#hidden.md', <<<'EOT'
        ---
        view::extends: layout
        title: not hidden title
        created_at: 2022-05-06
        ---
        # not hidden
        EOT);

        $service = new DocumentImportService(Post::class);
        $result = $service->import();

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['imported']);

        $this->assertDatabaseMissing('documents', ['path' => '#hidden.md']);
        $this->assertDatabaseHas('documents', ['path' => 'not#hidden.md']);
    }

    public function test_it_excludes_all_files_inside_a_hash_prefixed_folder(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('posts/#tools/git.md', <<<'EOT'
        ---
        view::extends: layout
        title: git tool
        created_at: 2022-05-06
        ---
        # git
        EOT);

        Storage::disk('content')->put('posts/tools/git.md', <<<'EOT'
        ---
        view::extends: layout
        title: visible git tool
        created_at: 2022-05-06
        ---
        # git
        EOT);

        $service = new DocumentImportService(Post::class);
        $result = $service->import();

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame([], $result['invalid_documents']);

        $this->assertDatabaseMissing('documents', ['path' => '#tools/git.md']);
        $this->assertDatabaseHas('documents', ['path' => 'tools/git.md']);
    }

    public function test_import_all_configured_models_excludes_hash_prefixed_file_for_one_content_type(): void
    {
        Storage::fake('content');

        config(['yazar.content_types' => [
            'posts' => Post::class,
            'pages' => Page::class,
        ]]);

        Storage::disk('content')->put('posts/#draft.md', <<<'EOT'
        ---
        view::extends: layout
        title: draft post
        created_at: 2022-05-06
        ---
        # draft post
        EOT);

        Storage::disk('content')->put('pages/about.md', <<<'EOT'
        ---
        view::extends: layout
        title: about page
        created_at: 2022-05-06
        ---
        # about page
        EOT);

        DocumentImportService::importAllConfiguredModels();

        $this->assertDatabaseMissing('documents', ['path' => '#draft.md', 'type' => 'post']);
        $this->assertDatabaseHas('documents', ['path' => 'about.md', 'type' => 'page']);
        $this->assertSame(1, Document::count());
    }

    public function test_import_all_configured_models_imports_every_content_type(): void
    {
        Storage::fake('content');

        config(['yazar.content_types' => [
            'posts' => Post::class,
            'pages' => Page::class,
            'categories' => Category::class,
        ]]);

        Storage::disk('content')->put('posts/hello.md', <<<'EOT'
        ---
        view::extends: layout
        title: hello post
        created_at: 2022-05-06
        ---
        # hello post
        EOT);

        Storage::disk('content')->put('pages/about.md', <<<'EOT'
        ---
        view::extends: layout
        title: about page
        created_at: 2022-05-06
        ---
        # about page
        EOT);

        Storage::disk('content')->put('categories/laravel.md', <<<'EOT'
        ---
        view::extends: category
        title: laravel category
        created_at: 2022-05-06
        ---
        # laravel category
        EOT);

        DocumentImportService::importAllConfiguredModels();

        $this->assertDatabaseHas('documents', ['path' => 'hello.md', 'type' => 'post']);
        $this->assertDatabaseHas('documents', ['path' => 'about.md', 'type' => 'page']);
        $this->assertDatabaseHas('documents', ['path' => 'laravel.md', 'type' => 'category']);
        $this->assertSame(3, Document::count());
    }
}
