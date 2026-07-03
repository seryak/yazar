<?php

namespace Yazar\Models;

use Illuminate\Database\Eloquent\Builder;
use Yazar\Enums\DocumentType;

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
