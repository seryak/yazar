<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Post;

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
        static::addGlobalScope('category_type', function (Builder $builder): void {
            $builder->where('type', DocumentType::Category);
        });

        static::creating(function (self $document): void {
            $document->type = DocumentType::Category->value;
        });
    }
}
