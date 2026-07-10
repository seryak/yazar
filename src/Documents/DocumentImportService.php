<?php

namespace Yazar\Documents;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\CommonMark\Exception\CommonMarkException;
use Yazar\Contracts\Documentable;
use Yazar\Markdown\Extensions\DiskUrlResolutionException;
use Yazar\Markdown\MarkdownParser;
use Yazar\Models\Document;

class DocumentImportService
{
    private const DISK = 'content';

    /**
     * @var list<string>
     */
    private const REQUIRED_META_FIELDS = [
        'view::extends',
        'title',
        'created_at',
    ];

    /**
     * @param  class-string<Document&Documentable>  $modelClass
     */
    public function __construct(
        private readonly string $modelClass,
    ) {}

    /**
     * @return array{total:int, imported:int, invalid_documents:list<string>}
     */
    public function import(): array
    {
        $files = Storage::disk(self::DISK)->allFiles($this->modelClass::documentsPath());
        $invalidDocuments = [];
        $imported = 0;
        foreach ($files as $filePath) {
            if ($this->isValidFile($filePath)) {
                $this->importFile($filePath);
            } else {
                $invalidDocuments[] = $this->stripSubfolder($filePath);

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

    public static function importAllConfiguredModels(): void
    {
        foreach (config('yazar.content_types', []) as $modelClass) {
            $service = new self($modelClass);
            $service->import();
        }
    }

    private function importFile(string $filePath): void
    {
        $content = Storage::disk(self::DISK)->get($filePath)
            ?? throw new \RuntimeException("File not readable: $filePath");
        $parser = new MarkdownParser;
        $parser->parse($content);

        $path = $this->stripSubfolder($filePath);

        $this->modelClass::updateOrCreate(
            ['path' => $path, 'type' => $this->modelClass::documentType()],
            [
                'meta' => $parser->options,
                'slug' => Str::replace('.md', '', $path),
                'content' => $parser->markdownContent,
                'published_at' => $parser->options['created_at'],
            ]
        );
    }

    private function stripSubfolder(string $filePath): string
    {
        return Str::after($filePath, $this->modelClass::documentsPath().'/');
    }

    private function isValidFile(string $filePath): bool
    {
        $content = Storage::disk(self::DISK)->get($filePath);
        if ($content === null) {
            return false;
        }
        $parser = new MarkdownParser;

        try {
            $parser->parse($content);
        } catch (CommonMarkException|DiskUrlResolutionException) {
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

        if (! view()->exists((string) $meta['view::extends'])) {
            return false;
        }

        return true;
    }
}
