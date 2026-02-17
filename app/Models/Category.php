<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Category extends Document
{
    public function posts(): HasMany|Category
    {
        return $this->hasMany(Post::class, 'meta->category','slug');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('category_type', function (Builder $builder): void {
            $builder->where('type', DocumentType::Category);
        });

        static::creating(function (self $document): void {
            $document->type = DocumentType::Category;
        });
    }
}
