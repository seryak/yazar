<?php

namespace Yazar\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Yazar\Casts\DocumentMetaCast;
use Yazar\Contracts\Documentable;
use Yazar\Markdown\MarkdownParser;
use Yazar\ValueObjects\DocumentMeta;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property string $path
 * @property string $slug
 * @property string $content
 * @property string $type
 * @property DocumentMeta $meta
 * @property-read string $html_content
 * @property Carbon $published_at
 */
class Document extends Model
{
    protected $table = 'documents';

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
        // Filter through the model instance (not self::/static::documentType()
        // directly) so a query on the bare Document class — which isn't Documentable —
        // skips the filter instead of fatal-erroring on a missing method. This lets
        // cross-type operations like Document::count()/truncate() keep working.
        static::addGlobalScope('document_type', function (Builder $builder): void {
            $model = $builder->getModel();

            if ($model instanceof Documentable) {
                $builder->where('type', $model::documentType());
            }
        });

        static::creating(function (self $document): void {
            self::validateAndNormalizeType($document);
        });

        static::updating(function (self $document): void {
            self::validateAndNormalizeType($document);
        });
    }

    private static function validateAndNormalizeType(self $document): void
    {
        if (! $document instanceof Documentable) {
            throw new InvalidArgumentException(
                'Cannot create or update the base Document model directly; use a concrete subclass (e.g. Post, Page, Category) instead.'
            );
        }

        $explicitType = $document->getAttributes()['type'] ?? null;

        if ($explicitType !== null && $explicitType !== $document::documentType()) {
            throw new InvalidArgumentException(
                "Document type '{$explicitType}' does not match ".$document::class." (expected '{$document::documentType()}')."
            );
        }

        $document->type = $document::documentType();
    }

    public function getHtmlContentAttribute(): string
    {
        $parser = new MarkdownParser;
        $parser->parse($this->content ?? '');

        return $parser->content;
    }

    public function getPathForStaticPageAttribute(): string
    {
        $path = Str::replace('.md', '', $this->path);

        return config('yazar.use_html_suffix') ? $path.'.html' : $path.'/index.html';
    }
}
