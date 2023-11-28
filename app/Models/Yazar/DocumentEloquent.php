<?php

namespace App\Models\Yazar;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string $title
 * @property string $slug
 * @property string $path
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
            $this->attributes['slug'] = Str::replace('.md', '', $this->filePath);
        }

        return $this->attributes['slug'];
    }

    public function getPathAttribute(): string
    {
        $path = Str::replace('.md', '', $this->filePath);
        return config('content.use_html_suffix') ? $path. '.html' : $path.'/index.html';
    }


}
