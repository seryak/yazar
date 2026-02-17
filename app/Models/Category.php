<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Builder;

class Category extends Document
{
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
