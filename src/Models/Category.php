<?php

namespace Yazar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Collection<int, Post> $posts
 */
class Category extends Document
{
    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'meta->category', 'slug');
    }

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('category_type', function (Builder $builder): void {
            $builder->where('type', 'category');
        });

        static::creating(function (self $document): void {
            $document->type = 'category';
        });
    }
}
