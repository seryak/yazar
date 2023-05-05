<?php

return [
    'use_html_suffix' => env('USE_HTML_SUFFIX', false),
    'output_directory' => env('OUTPUT_DIRECTORY', 'build'),
    'collections' => [
        'pages' => [
            'sorting' => false,
            'path' => '{filename}',
            'items' => [
                content_path('hello.md'),
                content_path('test.md'),
            ],
        ],
        'jetbrains-posts' => [
            'sorting' => false,
            'path' => 'blog/jetbrains/{filename}',
            'items' => [
                content_path('hello.md'),
                content_path('test.md'),
            ],
        ],
    ]
];
