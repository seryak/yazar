<?php

use Yazar\Enums\DocumentType;
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
        'posts' => ['type' => DocumentType::Post, 'model' => Post::class],
        'pages' => ['type' => DocumentType::Page, 'model' => Page::class],
        'categories' => ['type' => DocumentType::Category, 'model' => Category::class],
    ],

    // Ключи здесь ДОЛЖНЫ совпадать с ключами content_types — DocumentImportService
    // резолвит Storage-диск по тому же имени ('posts'/'pages'/'categories').
    'disks' => [
        'posts' => ['driver' => 'local', 'root' => $contentPath.'/posts', 'throw' => false],
        'pages' => ['driver' => 'local', 'root' => $contentPath.'/pages', 'throw' => false],
        'categories' => ['driver' => 'local', 'root' => $contentPath.'/categories', 'throw' => false],
        'static_output' => [
            'driver' => 'local',
            'root' => public_path(env('OUTPUT_DIRECTORY', 'build')),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],
    ],
];
