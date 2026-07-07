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
    'output_directory' => env('OUTPUT_DIRECTORY', 'build'),
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
    ],

    'markdown' => [
        'extensions' => [
            // \Yazar\Markdown\Extensions\PhikiHighlightExtension::class,
        ],
        'phiki' => [
            'theme' => env('YAZAR_CODE_THEME', 'github-light'),
            'default_grammar' => env('YAZAR_CODE_DEFAULT_GRAMMAR', 'shellscript'),
        ],
    ],
];
