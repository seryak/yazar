<?php

use Yazar\Models\Category;
use Yazar\Models\Page;
use Yazar\Models\Post;

$contentPath = env('YAZAR_CONTENT_PATH', base_path('_content'));

return [
    'content_path' => $contentPath,
    'deploy_target' => env('YAZAR_DEPLOY_TARGET'),
    'front_page_view' => env('YAZAR_FRONT_PAGE_VIEW', 'front-page'),
    'render_mode' => env('CONTENT_RENDER_MODE', 'dynamic'),
    'pagination_per_page' => env('CONTENT_PAGINATION_PER_PAGE', 1),
    'use_html_suffix' => env('USE_HTML_SUFFIX', false),
    'storage_url' => env('STORAGE_URL', ''),

    'content_types' => [
        'posts' => Post::class,
        'pages' => Page::class,
        'categories' => Category::class,
    ],

    'disks' => [
        'content' => ['driver' => 'local', 'root' => $contentPath, 'throw' => false],
        'static_output' => [
            'driver' => 'local',
            'root' => public_path(env('OUTPUT_DIRECTORY', 'build')),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],
        'imgproxy_build_cache' => [
            'driver' => 'local',
            'root' => storage_path('app/'.trim(env('YAZAR_IMGPROXY_CACHE_DIRECTORY', 'imgproxy-cache'), '/')),
            'visibility' => 'public',
            'throw' => false,
        ],
        'imgproxy_cache' => [
            'driver' => 'local',
            'root' => public_path('imgproxy-cache'),
            'url' => '/imgproxy-cache',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'markdown' => [
        'extensions' => [
            // \Yazar\Markdown\Extensions\PhikiHighlightExtension::class,
            // \Yazar\Markdown\Extensions\ImgproxyExtension::class,
            //  DiskUrlExtension::class,
        ],
        'default_disk' => env('YAZAR_MARKDOWN_DEFAULT_DISK'),
        'phiki' => [
            'theme' => env('YAZAR_CODE_THEME', 'github-light'),
            'default_grammar' => env('YAZAR_CODE_DEFAULT_GRAMMAR', 'shellscript'),
        ],
    ],

    'imgproxy' => [
        'base_url' => env('IMGPROXY_BASE_URL', 'http://127.0.0.1:6066'),
        'key' => env('IMGPROXY_KEY'),
        'salt' => env('IMGPROXY_SALT'),
        'presets' => [
            // 'post-cover' => 'rs:fit:1200:630/q:80/f:webp',
        ],
    ],
];
