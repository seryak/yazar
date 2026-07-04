<?php

namespace Yazar\Models;

use Illuminate\Database\Eloquent\Builder;

class Page extends Document
{
    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('page_type', function (Builder $builder): void {
            $builder->where('type', 'page');
        });

        static::creating(function (self $document): void {
            $document->type = 'page';
        });
    }
}
