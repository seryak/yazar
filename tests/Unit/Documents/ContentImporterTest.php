<?php

namespace Tests\Unit\Documents;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yazar\Documents\ContentImporter;
use Yazar\Models\Document;
use Yazar\Models\Page;
use Yazar\Models\Post;

#[CoversClass(ContentImporter::class)]
class ContentImporterTest extends TestCase
{
    use RefreshDatabase;

    #[TestDox('reimportAll() truncates existing documents before reimporting')]
    public function test_reimport_all_truncates_existing_documents_before_reimporting(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/stale.md', <<<'EOT'
        ---
        view::extends: layout
        title: stale title
        created_at: "2022-05-06"
        ---
        # stale
        EOT);

        (new ContentImporter)->importAll();

        $this->assertDatabaseHas('documents', ['path' => 'stale.md']);

        Storage::disk('content')->delete('pages/stale.md');
        Storage::disk('content')->put('pages/fresh.md', <<<'EOT'
        ---
        view::extends: layout
        title: fresh title
        created_at: "2022-05-07"
        ---
        # fresh
        EOT);

        (new ContentImporter)->reimportAll();

        $this->assertDatabaseMissing('documents', ['path' => 'stale.md']);
        $this->assertDatabaseHas('documents', ['path' => 'fresh.md']);
        $this->assertDatabaseCount(Document::TABLE, 1);
    }

    #[TestDox('importIfEmpty() does nothing when documents already exist')]
    public function test_import_if_empty_does_nothing_when_documents_already_exist(): void
    {
        Page::create([
            'path' => 'existing.md',
            'slug' => 'existing',
            'url' => 'existing',
            'meta' => [
                'view::extends' => 'page',
                'title' => 'Existing page',
                'created_at' => 20220506,
            ],
            'content' => '# existing',
            'published_at' => now(),
        ]);

        Storage::fake('content');

        Storage::disk('content')->put('pages/other.md', <<<'EOT'
        ---
        view::extends: layout
        title: other title
        created_at: "2022-05-06"
        ---
        # other
        EOT);

        (new ContentImporter)->importIfEmpty();

        $this->assertDatabaseMissing('documents', ['path' => 'other.md']);
        $this->assertDatabaseCount(Document::TABLE, 1);
    }

    #[TestDox('importIfEmpty() imports when the documents table is empty')]
    public function test_import_if_empty_imports_when_the_documents_table_is_empty(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/about.md', <<<'EOT'
        ---
        view::extends: layout
        title: about title
        created_at: "2022-05-06"
        ---
        # about
        EOT);

        (new ContentImporter)->importIfEmpty();

        $this->assertDatabaseHas('documents', ['path' => 'about.md']);
        $this->assertDatabaseCount(Document::TABLE, 1);
    }

    #[TestDox('importAll() upserts without deleting documents missing from storage')]
    public function test_import_all_upserts_without_deleting_documents_missing_from_storage(): void
    {
        Storage::fake('content');

        Storage::disk('content')->put('pages/kept.md', <<<'EOT'
        ---
        view::extends: layout
        title: kept title
        created_at: "2022-05-06"
        ---
        # kept
        EOT);

        (new ContentImporter)->importAll();

        Storage::disk('content')->delete('pages/kept.md');
        Storage::disk('content')->put('pages/new.md', <<<'EOT'
        ---
        view::extends: layout
        title: new title
        created_at: "2022-05-07"
        ---
        # new
        EOT);

        (new ContentImporter)->importAll();

        $this->assertDatabaseHas('documents', ['path' => 'kept.md']);
        $this->assertDatabaseHas('documents', ['path' => 'new.md']);
        $this->assertDatabaseCount(Document::TABLE, 2);
    }

    #[TestDox('a url conflict between different content types is aggregated by importAll()')]
    public function test_url_conflict_between_content_types_is_aggregated_by_import_all(): void
    {
        Storage::fake('content');

        config(['yazar.content_types' => [
            'posts' => Post::class,
            'pages' => Page::class,
        ]]);

        Storage::disk('content')->put('posts/about.md', <<<'EOT'
        ---
        view::extends: layout
        title: about post
        created_at: "2022-05-06"
        url: about
        ---
        # about post
        EOT);

        Storage::disk('content')->put('pages/about.md', <<<'EOT'
        ---
        view::extends: layout
        title: about page
        created_at: "2022-05-06"
        url: about
        ---
        # about page
        EOT);

        $conflicts = (new ContentImporter)->importAll();

        $this->assertSame(['about.md' => 'about'], $conflicts);
        $this->assertDatabaseCount(Document::TABLE, 1);
        $this->assertDatabaseHas('documents', ['path' => 'about.md', 'type' => 'post', 'url' => 'about']);
        $this->assertDatabaseMissing('documents', ['path' => 'about.md', 'type' => 'page']);
    }
}
