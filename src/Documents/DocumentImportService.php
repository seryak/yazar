<?php

namespace Yazar\Documents;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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
     * SQLite/MySQL/PostgreSQL all report a unique-constraint violation under
     * this SQLSTATE class, which is what QueryException::getCode() surfaces.
     */
    private const DUPLICATE_ENTRY_SQLSTATE = '23000';

    /**
     * @var list<string>
     */
    private const REQUIRED_META_FIELDS = [
        'view::extends',
        'title',
        'created_at',
    ];

    /**
     * @var array<string, string> path => url that was already taken by another document
     */
    private array $urlConflicts = [];

    /**
     * @param  class-string<Document>  $modelClass
     */
    public function __construct(
        private readonly string $modelClass,
    ) {}

    /**
     * @return array{total:int, imported:int, invalid_documents:list<string>, url_conflicts:array<string,string>}
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
            $path = $this->stripSubfolder($filePath);

            if ($parsed === null || ! $this->persist($filePath, $parsed)) {
                if (! array_key_exists($path, $this->urlConflicts)) {
                    $invalidDocuments[] = $path;
                }

                continue;
            }

            $imported++;
        }

        return [
            'total' => count($files),
            'imported' => $imported,
            'invalid_documents' => $invalidDocuments,
            'url_conflicts' => $this->urlConflicts,
        ];
    }

    private function persist(string $filePath, ParsedDocument $parsed): bool
    {
        $path = $this->stripSubfolder($filePath);
        $type = $this->modelClass::documentType();

        $filenameSlug = Str::replace('.md', '', $path);
        $frontMatterSlug = $this->stringOption($parsed->options, 'slug');
        $frontMatterUrl = $this->stringOption($parsed->options, 'url');

        $slug = $frontMatterSlug ?? $filenameSlug;
        $url = $frontMatterUrl ?? PermalinkResolver::resolve($this->modelClass::permalink(), ['slug' => $slug]);
        $url = trim($url, '/');

        if ($this->urlConflictExists($url, $path, $type)) {
            $this->urlConflicts[$path] = $url;

            return false;
        }

        try {
            $this->modelClass::updateOrCreate(
                ['path' => $path, 'type' => $type],
                [
                    'meta' => $parsed->options,
                    'slug' => $slug,
                    'url' => $url,
                    'content' => $parsed->markdownContent,
                    'published_at' => Carbon::parse($parsed->options['created_at']),
                ]
            );
        } catch (InvalidArgumentException) {
            return false;
        } catch (QueryException $exception) {
            if ($exception->getCode() !== self::DUPLICATE_ENTRY_SQLSTATE) {
                throw $exception;
            }

            $this->urlConflicts[$path] = $url;

            return false;
        }

        return true;
    }

    private function urlConflictExists(string $url, string $ownPath, string $ownType): bool
    {
        return DB::table(Document::TABLE)
            ->where('url', $url)
            ->where(fn ($query) => $query->where('path', '!=', $ownPath)->orWhere('type', '!=', $ownType))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function stringOption(array $options, string $key): ?string
    {
        $value = $options[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
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
            'created_at' => ['required', 'string', 'date'],
            'slug' => ['sometimes', 'string'],
            'url' => ['sometimes', 'string'],
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
