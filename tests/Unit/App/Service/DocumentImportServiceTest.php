<?php

namespace Tests\Unit\App\Service;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yazar\Documents\ContentImporter;
use Yazar\Documents\DocumentImportService;
use Yazar\Markdown\Extensions\DiskUrlExtension;
use Yazar\Markdown\Extensions\ImgproxyExtension;
use Yazar\Models\Category;
use Yazar\Models\Document;
use Yazar\Models\Page;
use Yazar\Models\Post;

#[CoversMethod(DocumentImportService::class, 'import')]
#[CoversMethod(ContentImporter::class, 'importAll')]
class DocumentImportServiceTest extends TestCase
{
    use RefreshDatabase;

    #[TestDox('import() skips invalid documents and collects their paths in invalid_documents')]
    public function test_it_skips_invalid_documents_and_collects_invalid_paths(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/valid.md', <<<'EOT'
        ---
        view::extends: layout
        title: valid title
        created_at: "2022-05-06"
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

    #[TestDox('import() updates the existing document by path on reimport')]
    public function test_it_updates_existing_document_by_path_on_reimport(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/same-path.md', <<<'EOT'
        ---
        view::extends: layout
        title: first title
        created_at: "2022-05-06"
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
        created_at: "2022-05-07"
        ---
        # second
        EOT);

        $second = $service->import();

        $this->assertSame(1, $second['imported']);
        $this->assertSame([], $second['invalid_documents']);
        $this->assertDatabaseCount(Document::TABLE, 1);

        $document = Page::firstOrFail();

        $this->assertSame('same-path.md', $document->path);
        $this->assertSame('second title', $document->meta?->title);
        $this->assertSame('# second', $document->content);
    }

    #[TestDox('import() treats a document with malformed front matter as invalid')]
    public function test_it_treats_malformed_front_matter_as_invalid(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/broken.md', <<<'EOT'
        ---
        view::extends: [layout
        title: broken
        created_at: "2022-05-06"
        ---
        # broken
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(0, $result['imported']);
        $this->assertSame(['broken.md'], $result['invalid_documents']);
        $this->assertDatabaseMissing('documents', ['path' => 'broken.md']);
    }

    #[TestDox('import() treats a document with an unregistered view as invalid')]
    public function test_it_treats_document_with_unregistered_view_as_invalid(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/no-view.md', <<<'EOT'
        ---
        view::extends: this-view-does-not-exist
        title: no view
        created_at: "2022-05-06"
        ---
        # no view
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(0, $result['imported']);
        $this->assertSame(['no-view.md'], $result['invalid_documents']);
    }

    #[TestDox('import() treats a document with a blank required field as invalid')]
    public function test_it_treats_blank_required_field_as_invalid(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/blank-title.md', <<<'EOT'
        ---
        view::extends: layout
        title: " "
        created_at: "2022-05-06"
        ---
        # blank title
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(0, $result['imported']);
        $this->assertSame(['blank-title.md'], $result['invalid_documents']);
    }

    #[TestDox('import() marks a document with a broken disk link as invalid without failing the whole import')]
    public function test_it_treats_document_with_broken_disk_link_as_invalid_without_failing_the_whole_import(): void
    {
        Storage::fake('content');

        config(['yazar.markdown.extensions' => [DiskUrlExtension::class]]);

        Storage::disk('content')->put('pages/valid.md', <<<'EOT'
        ---
        view::extends: layout
        title: valid title
        created_at: "2022-05-06"
        ---
        # valid
        EOT);

        Storage::disk('content')->put('pages/broken-disk-link.md', <<<'EOT'
        ---
        view::extends: layout
        title: broken disk link
        created_at: "2022-05-06"
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

    #[TestDox('import() marks a document with a broken imgproxy link as invalid without failing the whole import')]
    public function test_it_treats_document_with_broken_imgproxy_link_as_invalid_without_failing_the_whole_import(): void
    {
        Storage::fake('content');

        config(['yazar.markdown.extensions' => [ImgproxyExtension::class]]);

        Storage::disk('content')->put('pages/valid.md', <<<'EOT'
        ---
        view::extends: layout
        title: valid title
        created_at: "2022-05-06"
        ---
        # valid
        EOT);

        Storage::disk('content')->put('pages/broken-imgproxy-link.md', <<<'EOT'
        ---
        view::extends: layout
        title: broken imgproxy link
        created_at: "2022-05-06"
        ---
        ![alt](imgproxy(https://example.com/photo.jpg, 'unknown-preset'))
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(2, $result['total']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame(['broken-imgproxy-link.md'], $result['invalid_documents']);

        $this->assertDatabaseHas('documents', ['path' => 'valid.md']);
        $this->assertDatabaseMissing('documents', ['path' => 'broken-imgproxy-link.md']);
    }

    #[TestDox('import() excludes files with a hash prefix from import')]
    public function test_it_excludes_files_with_hash_prefix_from_import(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/visible.md', <<<'EOT'
        ---
        view::extends: layout
        title: visible title
        created_at: "2022-05-06"
        ---
        # visible
        EOT);

        Storage::disk('content')->put('pages/#draft.md', <<<'EOT'
        ---
        view::extends: layout
        title: draft title
        created_at: "2022-05-06"
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

    #[TestDox('import() excludes a hash-prefixed file regardless of subfolder nesting')]
    public function test_it_excludes_hash_prefixed_file_regardless_of_subfolder(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('posts/#hidden.md', <<<'EOT'
        ---
        view::extends: layout
        title: hidden title
        created_at: "2022-05-06"
        ---
        # hidden
        EOT);

        Storage::disk('content')->put('posts/not#hidden.md', <<<'EOT'
        ---
        view::extends: layout
        title: not hidden title
        created_at: "2022-05-06"
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

    #[TestDox('import() excludes all files inside a hash-prefixed folder')]
    public function test_it_excludes_all_files_inside_a_hash_prefixed_folder(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('posts/#tools/git.md', <<<'EOT'
        ---
        view::extends: layout
        title: git tool
        created_at: "2022-05-06"
        ---
        # git
        EOT);

        Storage::disk('content')->put('posts/tools/git.md', <<<'EOT'
        ---
        view::extends: layout
        title: visible git tool
        created_at: "2022-05-06"
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

    #[TestDox('importAll() excludes a hash-prefixed file for one content type')]
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
        created_at: "2022-05-06"
        ---
        # draft post
        EOT);

        Storage::disk('content')->put('pages/about.md', <<<'EOT'
        ---
        view::extends: layout
        title: about page
        created_at: "2022-05-06"
        ---
        # about page
        EOT);

        (new ContentImporter)->importAll();

        $this->assertDatabaseMissing('documents', ['path' => '#draft.md', 'type' => 'post']);
        $this->assertDatabaseHas('documents', ['path' => 'about.md', 'type' => 'page']);
        $this->assertDatabaseCount(Document::TABLE, 1);
    }

    #[TestDox('importAll() imports every configured content type')]
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
        created_at: "2022-05-06"
        ---
        # hello post
        EOT);

        Storage::disk('content')->put('pages/about.md', <<<'EOT'
        ---
        view::extends: layout
        title: about page
        created_at: "2022-05-06"
        ---
        # about page
        EOT);

        Storage::disk('content')->put('categories/laravel.md', <<<'EOT'
        ---
        view::extends: category
        title: laravel category
        created_at: "2022-05-06"
        ---
        # laravel category
        EOT);

        (new ContentImporter)->importAll();

        $this->assertDatabaseHas('documents', ['path' => 'hello.md', 'type' => 'post']);
        $this->assertDatabaseHas('documents', ['path' => 'about.md', 'type' => 'page']);
        $this->assertDatabaseHas('documents', ['path' => 'laravel.md', 'type' => 'category']);
        $this->assertDatabaseCount(Document::TABLE, 3);
    }

    #[TestDox('created_at front matter with a date-only value is parsed into the correct published_at date')]
    public function test_created_at_front_matter_with_date_only_is_parsed_correctly(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/dated.md', <<<'EOT'
        ---
        view::extends: layout
        title: dated title
        created_at: "2016-11-22"
        ---
        # dated
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(1, $result['imported']);

        $document = Page::firstOrFail();

        $this->assertSame('2016-11-22 00:00:00', $document->published_at?->format('Y-m-d H:i:s'));
    }

    #[TestDox('created_at front matter with a date and time value is parsed into the correct published_at moment')]
    public function test_created_at_front_matter_with_date_and_time_is_parsed_correctly(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/dated-with-time.md', <<<'EOT'
        ---
        view::extends: layout
        title: dated with time title
        created_at: "2016-11-22 14:30:00"
        ---
        # dated with time
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(1, $result['imported']);

        $document = Page::firstOrFail();

        $this->assertSame('2016-11-22 14:30:00', $document->published_at?->format('Y-m-d H:i:s'));
    }

    #[TestDox('created_at front matter without quotes is treated as invalid because YAML parses it as a number, not a date string')]
    public function test_unquoted_created_at_is_treated_as_invalid(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/unquoted.md', <<<'EOT'
        ---
        view::extends: layout
        title: unquoted date title
        created_at: 2016-11-22
        ---
        # unquoted
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(0, $result['imported']);
        $this->assertSame(['unquoted.md'], $result['invalid_documents']);
    }

    #[TestDox('front matter slug overrides the computed slug and url')]
    public function test_front_matter_slug_overrides_computed_slug_and_url(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/original-name.md', <<<'EOT'
        ---
        view::extends: layout
        title: original title
        created_at: "2022-05-06"
        slug: custom-slug
        ---
        # original
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(1, $result['imported']);
        $this->assertDatabaseHas('documents', [
            'path' => 'original-name.md',
            'slug' => 'custom-slug',
            'url' => 'custom-slug',
        ]);
    }

    #[TestDox('front matter url overrides the whole path, ignoring the model permalink pattern')]
    public function test_front_matter_url_overrides_the_whole_path(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('posts/example.md', <<<'EOT'
        ---
        view::extends: layout
        title: example title
        created_at: "2022-05-06"
        url: archive/legacy
        ---
        # example
        EOT);

        $service = new DocumentImportService(Post::class);
        $result = $service->import();

        $this->assertSame(1, $result['imported']);
        $this->assertDatabaseHas('documents', [
            'path' => 'example.md',
            'url' => 'archive/legacy',
        ]);
    }

    #[TestDox('a document with a colliding url is skipped and reported in url_conflicts, not invalid_documents')]
    public function test_document_with_colliding_url_is_reported_in_url_conflicts(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/one.md', <<<'EOT'
        ---
        view::extends: layout
        title: one title
        created_at: "2022-05-06"
        slug: same-slug
        ---
        # one
        EOT);

        Storage::disk('content')->put('pages/two.md', <<<'EOT'
        ---
        view::extends: layout
        title: two title
        created_at: "2022-05-07"
        slug: same-slug
        ---
        # two
        EOT);

        $service = new DocumentImportService(Page::class);
        $result = $service->import();

        $this->assertSame(2, $result['total']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame([], $result['invalid_documents']);
        $this->assertSame(['two.md' => 'same-slug'], $result['url_conflicts']);

        $this->assertDatabaseHas('documents', ['path' => 'one.md', 'url' => 'same-slug']);
        $this->assertDatabaseMissing('documents', ['path' => 'two.md']);
    }
}
