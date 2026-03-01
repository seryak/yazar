<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Builder;

class Post extends Document
{
    protected static function booted(): void
    {
        static::addGlobalScope('post_type', function (Builder $builder): void {
            $builder->where('type', DocumentType::Post);
        });

        static::creating(function (self $document): void {
            $document->type = DocumentType::Post->value;
        });
    }
}
