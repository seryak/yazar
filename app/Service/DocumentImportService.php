<?php

namespace App\Service;

use App\Enums\DocumentType;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\CommonMark\Exception\CommonMarkException;

class DocumentImportService
{
    /**
     * @var list<string>
     */
    private const REQUIRED_META_FIELDS = [
        'view::extends',
        'title',
        'created_at',
    ];

    public function __construct(
        private readonly string $diskName,
        private readonly DocumentType $type,
    ) {}

    /**
     * @return array{total:int, imported:int, invalid_documents:list<string>}
     */
    public function import(): array
    {
        $files = Storage::disk($this->diskName)->allFiles();
        $invalidDocuments = [];
        $imported = 0;
        foreach ($files as $filePath) {
            if ($this->isValidFile($filePath)) {
                $this->importFile($filePath);
            } else {
                $invalidDocuments[] = $filePath;

                continue;
            }

            $imported++;
        }

        return [
            'total' => count($files),
            'imported' => $imported,
            'invalid_documents' => $invalidDocuments,
        ];
    }

    private function importFile(string $filePath): void
    {
        $content = Storage::disk($this->diskName)->get($filePath);
        $parser = new MarkdownParser;
        $parser->parse($content);

        Document::updateOrCreate(
            ['path' => $filePath, 'type' => $this->type->value],
            [
                'meta' => $parser->options,
                'slug' => Str::replace('.md', '', $filePath),
                'content' => $parser->markdownContent,
                'published_at' => $parser->options['created_at'],
            ]
        );
    }

    private function isValidFile(string $filePath): bool
    {
        $content = Storage::disk($this->diskName)->get($filePath);
        $parser = new MarkdownParser;

        try {
            $parser->parse($content);
        } catch (CommonMarkException) {
            return false;
        }

        if (! $this->isValidOptions($parser->options)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function isValidOptions(array $meta): bool
    {
        $validator = Validator::make($meta, [
            'view::extends' => ['required', 'string'],
            'title' => ['required', 'string'],
            'created_at' => ['required', 'integer'],
        ]);

        if (! $validator->passes()) {
            return false;
        }

        foreach (self::REQUIRED_META_FIELDS as $field) {
            if (! is_scalar($meta[$field])) {
                return false;
            }
            if (trim((string) $meta[$field]) === '') {
                return false;
            }
        }

        return true;
    }
}
