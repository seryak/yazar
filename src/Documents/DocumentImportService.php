<?php

namespace Yazar\Documents;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use League\CommonMark\Exception\CommonMarkException;
use Yazar\Markdown\Extensions\DiskUrlResolutionException;
use Yazar\Markdown\Extensions\ImgproxyResolutionException;
use Yazar\Markdown\MarkdownParser;
use Yazar\Markdown\ParsedDocument;
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
     * @param  class-string<Document>  $modelClass
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
        $files = array_values(array_filter(
            $files,
            fn (string $filePath): bool => ! $this->isHiddenFile($filePath),
        ));

        $invalidDocuments = [];
        $imported = 0;
        foreach ($files as $filePath) {
            $parsed = $this->tryParse($filePath);
            if ($parsed === null || ! $this->persist($filePath, $parsed)) {
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

    private function persist(string $filePath, ParsedDocument $parsed): bool
    {
        $path = $this->stripSubfolder($filePath);

        try {
            $this->modelClass::updateOrCreate(
                ['path' => $path, 'type' => $this->modelClass::documentType()],
                [
                    'meta' => $parsed->options,
                    'slug' => Str::replace('.md', '', $path),
                    'content' => $parsed->markdownContent,
                    'published_at' => $parsed->options['created_at'],
                ]
            );
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    private function stripSubfolder(string $filePath): string
    {
        return Str::after($filePath, $this->modelClass::documentsPath().'/');
    }

    private function isHiddenFile(string $filePath): bool
    {
        foreach (explode('/', $filePath) as $segment) {
            if (str_starts_with($segment, '#')) {
                return true;
            }
        }

        return false;
    }

    private function tryParse(string $filePath): ?ParsedDocument
    {
        $content = Storage::disk(self::DISK)->get($filePath);
        if ($content === null) {
            return null;
        }
        $parser = app(MarkdownParser::class);

        try {
            $parser->parse($content);
        } catch (CommonMarkException|DiskUrlResolutionException|ImgproxyResolutionException) {
            return null;
        }

        if (! $this->isValidOptions($parser->options)) {
            return null;
        }

        return new ParsedDocument($parser->options, $parser->markdownContent);
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
