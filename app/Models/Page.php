<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Builder;

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
