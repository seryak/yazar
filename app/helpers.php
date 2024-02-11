<?php

if (!function_exists('test')) {
    function test()
    {
        dd('test');
    }
}

if (!function_exists('content_path')) {
    function content_path(string $path): string
    {
        return base_path('_content/' . $path);
    }
}

if (!function_exists('storage')) {
    function storage(string $path): string
    {
        return env('STORAGE_URL') . $path;
    }
}
