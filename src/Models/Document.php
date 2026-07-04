<?php

namespace Yazar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Yazar\Casts\DocumentMetaCast;
use Yazar\Enums\DocumentType;
use Yazar\Markdown\MarkdownParser;
use Yazar\ValueObjects\DocumentMeta;

/**
 * @property string $path
 * @property string $slug
 * @property string $content
 * @property string $type
 * @property DocumentMeta $meta
 * @property-read string $html_content
 * @property \Carbon\Carbon $published_at
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
            'type' => DocumentType::class,
            'published_at' => 'datetime',
        ];
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
