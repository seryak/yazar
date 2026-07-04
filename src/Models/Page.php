<?php

namespace Yazar\Models;

use Illuminate\Database\Eloquent\Builder;
use Yazar\Contracts\Documentable;

class Page extends Document implements Documentable
{
    protected const TYPE = 'page';

    public static function documentType(): string
    {
        return self::TYPE;
    }

    protected static function booted(): void
    {
        static::addGlobalScope('page_type', function (Builder $builder): void {
            $builder->where('type', self::TYPE);
        });

        static::creating(function (self $document): void {
            $document->type ??= self::TYPE;
        });

        parent::booted();
    }
}
