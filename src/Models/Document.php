<?php

namespace Yazar\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Yazar\Casts\DocumentMetaCast;
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
        static::saving(function (self $document): void {
            $validTypes = array_column(config('yazar.content_types', []), 'type');

            if (! in_array($document->type, $validTypes, true)) {
                throw new InvalidArgumentException("Invalid document type '{$document->type}'.");
            }
        });
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
