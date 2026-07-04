<?php

namespace Tests\Unit\App\Service;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Yazar\Documents\DocumentImportService;
use Yazar\Models\Document;

class DocumentImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_skips_invalid_documents_and_collects_invalid_paths(): void
    {
        Storage::fake('documents');

        Storage::disk('documents')->put('valid.md', <<<'EOT'
        ---
        view::extends: layout
        title: valid title
        created_at: 2022-05-06
        ---
        # valid
        EOT);

        Storage::disk('documents')->put('invalid.md', <<<'EOT'
        ---
        view::extends: layout
        title: invalid title
        ---
        # invalid
        EOT);

        $service = new DocumentImportService('documents', 'page');
        $result = $service->import();

        $this->assertSame(2, $result['total']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame(['invalid.md'], $result['invalid_documents']);

        $this->assertDatabaseHas('documents', ['path' => 'valid.md']);
        $this->assertDatabaseMissing('documents', ['path' => 'invalid.md']);
    }

    public function test_it_updates_existing_document_by_path_on_reimport(): void
    {
        Storage::fake('documents');

        Storage::disk('documents')->put('same-path.md', <<<'EOT'
        ---
        view::extends: layout
        title: first title
        created_at: 2022-05-06
        ---
        # first
        EOT);

        $service = new DocumentImportService('documents', 'page');
        $first = $service->import();

        $this->assertSame(1, $first['imported']);

        Storage::disk('documents')->put('same-path.md', <<<'EOT'
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
}
