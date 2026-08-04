<?php

namespace Tests\Unit\Build;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yazar\Build\ImgproxyBuildResolver;

#[CoversClass(ImgproxyBuildResolver::class)]
class ImgproxyBuildResolverTest extends TestCase
{
    private function fakeDisks(): void
    {
        Storage::fake('static_output');
        Storage::fake('imgproxy_build_cache');
        Storage::fake('imgproxy_cache');
    }

    private function setBaseUrl(string $baseUrl = 'http://imgproxy.test'): string
    {
        config(['yazar.imgproxy.base_url' => $baseUrl]);

        return $baseUrl;
    }

    /**
     * {@see ImgproxyBuildResolver::resolve()}
     */
    #[TestDox('resolve() replaces a single imgproxy link with a cached static path')]
    public function test_replaces_a_single_imgproxy_link_with_a_cached_static_path(): void
    {
        $this->fakeDisks();
        $baseUrl = $this->setBaseUrl();
        config(['yazar.imgproxy.presets.list-post-cover' => 'rs:fit:300:300/f:webp']);

        $url = "{$baseUrl}/sig/rs:fit:300:300/f:webp/plain/https://example.com/covers/photo.jpg";
        Storage::disk('static_output')->put('index.html', "<img src=\"{$url}\">");
        Http::fake([$url => Http::response('binary-image-data', 200, ['Content-Type' => 'image/webp'])]);

        $failures = (new ImgproxyBuildResolver)->resolve();

        $this->assertSame([], $failures);
        $html = Storage::disk('static_output')->get('index.html');
        $this->assertStringNotContainsString($url, $html);
        $this->assertTrue(Storage::disk('imgproxy_build_cache')->exists('list-post-cover/photo.jpg'));
        $this->assertStringContainsString('list-post-cover/photo.jpg', $html);
        Http::assertSentCount(1);
    }

    /**
     * {@see ImgproxyBuildResolver::presetKeyFromUrl()}
     */
    #[TestDox('presetKeyFromUrl() falls back to the unknown segment when options do not match any configured preset')]
    public function test_falls_back_to_the_unknown_segment_when_the_options_do_not_match_any_configured_preset(): void
    {
        $this->fakeDisks();
        $baseUrl = $this->setBaseUrl();
        config(['yazar.imgproxy.presets' => []]);

        $url = "{$baseUrl}/sig/rs:fit:300:300/f:webp/plain/https://example.com/covers/photo.jpg";
        Storage::disk('static_output')->put('index.html', "<img src=\"{$url}\">");
        Http::fake([$url => Http::response('bytes', 200, ['Content-Type' => 'image/webp'])]);

        (new ImgproxyBuildResolver)->resolve();

        $this->assertTrue(Storage::disk('imgproxy_build_cache')->exists('unknown/photo.jpg'));
    }

    /**
     * {@see ImgproxyBuildResolver::resolveUrl()}
     */
    #[TestDox('resolveUrl() downloads the same link only once across multiple files')]
    public function test_downloads_the_same_link_only_once_across_multiple_files(): void
    {
        $this->fakeDisks();
        $baseUrl = $this->setBaseUrl();
        config(['yazar.imgproxy.presets.list-post-cover' => 'rs:fit:300:300/f:webp']);

        $url = "{$baseUrl}/sig/rs:fit:300:300/f:webp/plain/https://example.com/covers/photo.jpg";
        Storage::disk('static_output')->put('a/index.html', "<img src=\"{$url}\">");
        Storage::disk('static_output')->put('b/index.html', "<img src=\"{$url}\">");
        Http::fake([$url => Http::response('binary-image-data', 200, ['Content-Type' => 'image/webp'])]);

        (new ImgproxyBuildResolver)->resolve();

        Http::assertSentCount(1);
    }

    /**
     * {@see ImgproxyBuildResolver::resolveUrl()}
     */
    #[TestDox('resolveUrl() does not redownload a link already cached from a previous build')]
    public function test_does_not_redownload_a_link_already_cached_from_a_previous_build(): void
    {
        $this->fakeDisks();
        $baseUrl = $this->setBaseUrl();
        config(['yazar.imgproxy.presets.list-post-cover' => 'rs:fit:300:300/f:webp']);

        $url = "{$baseUrl}/sig/rs:fit:300:300/f:webp/plain/https://example.com/covers/photo.jpg";
        Storage::disk('static_output')->put('index.html', "<img src=\"{$url}\">");
        Storage::disk('imgproxy_build_cache')->put('list-post-cover/photo.jpg', 'previously-cached-bytes');
        Http::fake();

        $failures = (new ImgproxyBuildResolver)->resolve();

        $this->assertSame([], $failures);
        Http::assertNothingSent();
        $html = Storage::disk('static_output')->get('index.html');
        $this->assertStringContainsString('list-post-cover/photo.jpg', $html);
    }

    /**
     * {@see ImgproxyBuildResolver::targetPath()}
     */
    #[TestDox('targetPath() keeps the original source filename regardless of the f: format token')]
    public function test_keeps_the_original_source_filename_regardless_of_the_f_format_token(): void
    {
        $this->fakeDisks();
        $baseUrl = $this->setBaseUrl();
        config(['yazar.imgproxy.presets.list-post-cover' => 'rs:fit:300:300/f:avif']);

        $url = "{$baseUrl}/sig/rs:fit:300:300/f:avif/plain/https://example.com/covers/photo.png";
        Storage::disk('static_output')->put('index.html', "<img src=\"{$url}\">");
        Http::fake([$url => Http::response('bytes', 200, ['Content-Type' => 'image/avif'])]);

        (new ImgproxyBuildResolver)->resolve();

        $this->assertTrue(Storage::disk('imgproxy_build_cache')->exists('list-post-cover/photo.png'));
    }

    /**
     * {@see ImgproxyBuildResolver::resolveUrl()}
     */
    #[TestDox('resolveUrl() leaves the link untouched when the response is not successful')]
    public function test_leaves_the_link_untouched_when_the_response_is_not_successful(): void
    {
        $this->fakeDisks();
        $baseUrl = $this->setBaseUrl();
        config(['yazar.imgproxy.presets.list-post-cover' => 'rs:fit:300:300']);

        $url = "{$baseUrl}/sig/rs:fit:300:300/plain/https://example.com/covers/photo.jpg";
        Storage::disk('static_output')->put('index.html', "<img src=\"{$url}\">");
        Http::fake([$url => Http::response('', 500)]);

        $failures = (new ImgproxyBuildResolver)->resolve();

        $this->assertSame(['HTTP 500'], array_values($failures));
        $html = Storage::disk('static_output')->get('index.html');
        $this->assertStringContainsString($url, $html);
        $this->assertFalse(Storage::disk('imgproxy_build_cache')->exists('list-post-cover/photo.jpg'));
    }

    /**
     * {@see ImgproxyBuildResolver::resolveUrl()}
     */
    #[TestDox('resolveUrl() leaves the link untouched on ConnectionException')]
    public function test_leaves_the_link_untouched_on_connection_exception(): void
    {
        $this->fakeDisks();
        $baseUrl = $this->setBaseUrl();

        $url = "{$baseUrl}/sig/rs:fit:300:300/plain/https://example.com/covers/photo.jpg";
        Storage::disk('static_output')->put('index.html', "<img src=\"{$url}\">");
        Http::fake(function () {
            throw new ConnectionException('Connection refused.');
        });

        $failures = (new ImgproxyBuildResolver)->resolve();

        $this->assertArrayHasKey($url, $failures);
        $this->assertSame('Connection refused.', $failures[$url]);
        $html = Storage::disk('static_output')->get('index.html');
        $this->assertStringContainsString($url, $html);
    }

    /**
     * {@see ImgproxyBuildResolver::resolve()}
     */
    #[TestDox('resolve() resolves links on paginated front page files without an .html extension')]
    public function test_resolves_links_on_paginated_front_page_files_without_an_html_extension(): void
    {
        $this->fakeDisks();
        $baseUrl = $this->setBaseUrl();
        config(['yazar.imgproxy.presets.list-post-cover' => 'rs:fit:300:300/f:webp']);

        $url = "{$baseUrl}/sig/rs:fit:300:300/f:webp/plain/https://example.com/covers/photo.jpg";
        Storage::disk('static_output')->put('/2', "<img src=\"{$url}\">");
        Http::fake([$url => Http::response('bytes', 200, ['Content-Type' => 'image/webp'])]);

        (new ImgproxyBuildResolver)->resolve();

        $html = Storage::disk('static_output')->get('/2');
        $this->assertStringNotContainsString($url, $html);
    }

    /**
     * {@see ImgproxyBuildResolver::resolve()}
     */
    #[TestDox('resolve() does not rewrite files without any imgproxy links')]
    public function test_does_not_rewrite_files_without_any_imgproxy_links(): void
    {
        $this->fakeDisks();
        $this->setBaseUrl();

        Storage::disk('static_output')->put('index.html', '<p>No images here.</p>');
        Http::fake();

        (new ImgproxyBuildResolver)->resolve();

        Http::assertNothingSent();
        $this->assertSame('<p>No images here.</p>', Storage::disk('static_output')->get('index.html'));
    }

    /**
     * {@see ImgproxyBuildResolver::publish()}
     */
    #[TestDox('publish() copies every build cache file onto the imgproxy_cache disk')]
    public function test_publish_copies_every_build_cache_file_onto_the_imgproxy_cache_disk(): void
    {
        $this->fakeDisks();
        Storage::disk('imgproxy_build_cache')->put('post-cover/photo.jpg', 'bytes-a');
        Storage::disk('imgproxy_build_cache')->put('list-post-cover/other.png', 'bytes-b');

        (new ImgproxyBuildResolver)->publish();

        $this->assertSame('bytes-a', Storage::disk('imgproxy_cache')->get('post-cover/photo.jpg'));
        $this->assertSame('bytes-b', Storage::disk('imgproxy_cache')->get('list-post-cover/other.png'));
    }

    /**
     * {@see ImgproxyBuildResolver::publish()}
     */
    #[TestDox('publish() does not overwrite a file already present on the imgproxy_cache disk')]
    public function test_publish_does_not_overwrite_a_file_already_present_on_the_imgproxy_cache_disk(): void
    {
        $this->fakeDisks();
        Storage::disk('imgproxy_build_cache')->put('post-cover/photo.jpg', 'new-bytes');
        Storage::disk('imgproxy_cache')->put('post-cover/photo.jpg', 'existing-bytes');

        (new ImgproxyBuildResolver)->publish();

        $this->assertSame('existing-bytes', Storage::disk('imgproxy_cache')->get('post-cover/photo.jpg'));
    }
}
