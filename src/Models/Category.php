<?php

namespace Yazar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Yazar\Contracts\Documentable;

/**
 * @property-read Collection<int, Post> $posts
 */
class Category extends Document implements Documentable
{
    protected const TYPE = 'category';

    public static function documentType(): string
    {
        return self::TYPE;
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'meta->category', 'slug');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('category_type', function (Builder $builder): void {
            $builder->where('type', self::TYPE);
        });

        static::creating(function (self $document): void {
            $document->type ??= self::TYPE;
        });

        parent::booted();
    }
}
