<?php

namespace App\Models\Yazar;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string $title
 * @property string $slug
 * @property PageDocument $fileDocument
 */
class DocumentEloquent extends Model
{


    public function getCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at']);
    }

    public function getFileDocumentAttribute(): FileDocument
    {
        return new PageDocument($this->filePath);
    }

    public function getSlugAttribute(): string
    {
        if (!$this->attributes['slug']) {
            $path = Str::replace('.md', '', $this->filePath);
            $this->attributes['slug'] = config('content.use_html_suffix') ? $path. '.html' : $path.'/index.html';
        }

        return $this->attributes['slug'];
    }


}
