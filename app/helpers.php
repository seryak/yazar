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
        return base_path('content/' . $path);
    }
}
