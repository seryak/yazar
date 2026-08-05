<?php

namespace Yazar\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Yazar\Contracts\Exporter;
use Yazar\Exporters\CategoryExporter;

/**
 * @property-read Collection<int, Post> $posts
 */
class Category extends Document
{
    public static function documentType(): string
    {
        return 'category';
    }

    public static function documentsPath(): string
    {
        return 'categories';
    }

    /**
     * @return class-string<Exporter>
     */
    public static function exporterClass(): string
    {
        return CategoryExporter::class;
    }

    public static function permalink(): string
    {
        return '/:slug';
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'meta->category', 'slug');
    }
}
