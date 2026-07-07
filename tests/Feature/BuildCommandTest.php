<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BuildCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_exports_posts_and_categories_but_not_pages(): void
    {
        Storage::fake('content');
        Storage::fake('static_output');

        Storage::disk('content')->put('categories/laravel.md', <<<'EOT'
        ---
        view::extends: category
        title: Laravel
        created_at: 2022-05-06
        ---
        # Laravel
        EOT);

        Storage::disk('content')->put('posts/hello-world.md', <<<'EOT'
        ---
        view::extends: page
        title: Hello World
        created_at: 2022-05-06
        category: laravel
        ---
        # Hello World
        EOT);

        Storage::disk('content')->put('pages/about.md', <<<'EOT'
        ---
        view::extends: page
        title: About
        created_at: 2022-05-06
        ---
        # About
        EOT);

        $this->artisan('build')->assertExitCode(0);

        $this->assertTrue(Storage::disk('static_output')->exists('hello-world/index.html'));
        $this->assertTrue(Storage::disk('static_output')->exists('laravel/index.html'));
        $this->assertFalse(Storage::disk('static_output')->exists('about/index.html'));
    }
}
