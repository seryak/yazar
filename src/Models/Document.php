<?php

namespace Yazar\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Yazar\Casts\DocumentMetaCast;
use Yazar\Contracts\Documentable;
use Yazar\Markdown\MarkdownParser;
use Yazar\ValueObjects\DocumentMeta;

/**
 * @property string $path
 * @property string $slug
 * @property string $content
 * @property string $type
 * @property DocumentMeta $meta
 * @property-read string $html_content
 * @property Carbon $published_at
 */
abstract class Document extends Model implements Documentable
{
    public const TABLE = 'documents';

    protected $table = self::TABLE;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'path',
        'meta',
        'content',
        'type',
        'slug',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => DocumentMetaCast::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Every instantiable Document is Documentable (the base class is abstract),
        // so the scope can rely on documentType() unconditionally. Cross-type work
        // that must see every row lives in Yazar\Documents\ContentImporter.
        static::addGlobalScope('document_type', function (Builder $builder): void {
            /** @var self $model */
            $model = $builder->getModel();

            $builder->where('type', $model::documentType());
        });

        static::saving(function (self $document): void {
            $explicitType = $document->getAttributes()['type'] ?? null;

            if ($explicitType !== null && $explicitType !== $document::documentType()) {
                throw new InvalidArgumentException(
                    "Document type '{$explicitType}' does not match ".$document::class." (expected '{$document::documentType()}')."
                );
            }

            $document->type = $document::documentType();
        });
    }

    /**
     * The stored `content` column already has its front matter stripped at
     * import time, so this only needs the markdown-to-HTML pass. Cached for the
     * lifetime of the instance: a Blade template touching it more than once
     * would otherwise re-render the whole document each time.
     *
     * @return Attribute<string, never>
     */
    protected function htmlContent(): Attribute
    {
        return Attribute::make(
            get: fn (): string => app(MarkdownParser::class)->toHtml($this->content ?? ''),
        )->shouldCache();
    }

    public function getPathForStaticPageAttribute(): string
    {
        $path = Str::replace('.md', '', $this->path);

        return config('yazar.use_html_suffix') ? $path.'.html' : $path.'/index.html';
    }
}
