<?php

namespace Yazar\Models;

use Illuminate\Database\Eloquent\Builder;
use Yazar\Contracts\Documentable;

class Post extends Document implements Documentable
{
    protected const TYPE = 'post';

    public static function documentType(): string
    {
        return self::TYPE;
    }

    protected static function booted(): void
    {
        static::addGlobalScope('post_type', function (Builder $builder): void {
            $builder->where('type', self::TYPE);
        });

        static::creating(function (self $document): void {
            $document->type ??= self::TYPE;
        });

        // parent::booted() registers Document's type validation on `creating`/`updating`.
        // It must run after the hook above and use `??=` (not unconditional assignment) so
        // an explicitly wrong `type` passed by the caller survives to be rejected by
        // validation, instead of being silently overwritten to self::TYPE.
        parent::booted();
    }
}
