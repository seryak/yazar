<?php

return [
    'use_html_suffix' => env('USE_HTML_SUFFIX', false),
    'output_directory' => env('OUTPUT_DIRECTORY', 'build'),
    'collections' => [
        'pages' => [
            'sorting' => false,
            'path' => '{fileName}',
            'items' => [
                content_path('pages/hello.md'),
                content_path('pages/test.md'),
            ],
        ],
        'jetbrains-posts' => [
            'sorting' => false,
            'path' => 'blog/jetbrains/{fileName}',
            'items' => [
                content_path('pages/hello.md'),
                content_path('pages/test.md'),
            ],
        ],
    ]
];
