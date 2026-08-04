<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yazar\Console\Commands\BuildCommand;
use Yazar\Markdown\Extensions\ImgproxyExtension;

#[CoversClass(BuildCommand::class)]
class BuildCommandTest extends TestCase
{
    use RefreshDatabase;

    private function putPostWithImgproxyLink(): void
    {
        config([
            'yazar.markdown.extensions' => [ImgproxyExtension::class],
            'yazar.imgproxy.base_url' => 'http://imgproxy.test',
            'yazar.imgproxy.key' => '68656c6c6f6b6579',
            'yazar.imgproxy.salt' => '776f726c6473616c74',
            'yazar.imgproxy.presets.post-cover' => 'rs:fit:300:300',
        ]);

        Storage::disk('content')->put('posts/hello-world.md', <<<'EOT'
        ---
        view::extends: page
        title: Hello World
        created_at: 2022-05-06
        ---
        # Hello World

        imgproxy(https://example.com/cover.jpg, 'post-cover')
        EOT);
    }

    #[TestDox('build exports posts and categories but skips pages')]
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

        $this->assertTrue(Storage::disk('static_output')->exists('blog/hello-world/index.html'));
        $this->assertTrue(Storage::disk('static_output')->exists('laravel/index.html'));
        $this->assertFalse(Storage::disk('static_output')->exists('about/index.html'));
    }

    #[TestDox('build replaces imgproxy links with cached static files')]
    public function test_build_replaces_imgproxy_links_with_cached_static_files(): void
    {
        Storage::fake('content');
        Storage::fake('static_output');
        Storage::fake('imgproxy_build_cache');
        Storage::fake('imgproxy_cache');
        $this->putPostWithImgproxyLink();
        Http::fake(['http://imgproxy.test/*' => Http::response('bytes', 200, ['Content-Type' => 'image/jpeg'])]);

        $this->artisan('build')->assertExitCode(0);

        $html = Storage::disk('static_output')->get('blog/hello-world/index.html');
        $this->assertStringNotContainsString('imgproxy.test', $html);
        $this->assertTrue(Storage::disk('imgproxy_cache')->exists('post-cover/cover.jpg'));
        $this->assertStringContainsString('post-cover/cover.jpg', $html);
    }

    #[TestDox('build does not fail when an imgproxy link cannot be downloaded')]
    public function test_build_does_not_fail_when_an_imgproxy_link_cannot_be_downloaded(): void
    {
        Storage::fake('content');
        Storage::fake('static_output');
        Storage::fake('imgproxy_build_cache');
        Storage::fake('imgproxy_cache');
        $this->putPostWithImgproxyLink();
        Http::fake(['http://imgproxy.test/*' => Http::response('', 500)]);

        $this->artisan('build')
            ->assertExitCode(0)
            ->expectsOutputToContain('imgproxy-ссылок не удалось скачать');

        $html = Storage::disk('static_output')->get('blog/hello-world/index.html');
        $this->assertStringContainsString('imgproxy.test', $html);
    }
}
