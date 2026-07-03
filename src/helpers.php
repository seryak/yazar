<?php

if (! function_exists('content_path')) {
    function content_path(string $path): string
    {
        return rtrim(config('yazar.content_path'), '/').'/'.$path;
    }
}

if (! function_exists('storage')) {
    function storage(string $path): string
    {
        return config('yazar.storage_url').$path;
    }
}
