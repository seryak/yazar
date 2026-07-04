<?php

namespace Yazar\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Yazar\Contracts\Documentable;

/**
 * @property-read Collection<int, Post> $posts
 */
class Category extends Document implements Documentable
{
    public static function documentType(): string
    {
        return 'category';
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'meta->category', 'slug');
    }
}
