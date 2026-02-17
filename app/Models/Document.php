<?php

namespace App\Models;

use App\Casts\DocumentMetaCast;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    public function getPathForStaticPageAttribute(): string
    {
        $path = Str::replace('.md', '', $this->path);
        return config('content.use_html_suffix') ? $path.'.html' : $path.'/index.html';
    }
}
