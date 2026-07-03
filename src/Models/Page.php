<?php

namespace Yazar\Models;

use Illuminate\Database\Eloquent\Builder;
use Yazar\Enums\DocumentType;

class Page extends Document
{
    protected static function booted(): void
    {
        static::addGlobalScope('page_type', function (Builder $builder): void {
            $builder->where('type', DocumentType::Page);
        });

        static::creating(function (self $document): void {
            $document->type = DocumentType::Page->value;
        });
    }
}
