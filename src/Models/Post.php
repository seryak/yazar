<?php

namespace Yazar\Models;

use Illuminate\Database\Eloquent\Builder;

class Post extends Document
{
    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('post_type', function (Builder $builder): void {
            $builder->where('type', 'post');
        });

        static::creating(function (self $document): void {
            $document->type = 'post';
        });
    }
}
