<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yazar\Console\Commands\ClearImgproxyCacheCommand;

#[CoversClass(ClearImgproxyCacheCommand::class)]
class ClearImgproxyCacheCommandTest extends TestCase
{
    #[TestDox('yazar:clear-imgproxy-cache deletes every file from both imgproxy cache disks')]
    public function test_it_deletes_every_file_from_both_imgproxy_cache_disks(): void
    {
        Storage::fake('imgproxy_build_cache');
        Storage::fake('imgproxy_cache');
        Storage::disk('imgproxy_build_cache')->put('post-cover/photo.jpg', 'bytes');
        Storage::disk('imgproxy_cache')->put('post-cover/photo.jpg', 'bytes');
        Storage::disk('imgproxy_cache')->put('list-post-cover/other.png', 'bytes');

        $this->artisan('yazar:clear-imgproxy-cache')->assertExitCode(0);

        $this->assertSame([], Storage::disk('imgproxy_build_cache')->allFiles());
        $this->assertSame([], Storage::disk('imgproxy_cache')->allFiles());
    }

    #[TestDox('yazar:clear-imgproxy-cache succeeds when the cache is already empty')]
    public function test_it_succeeds_when_the_cache_is_already_empty(): void
    {
        Storage::fake('imgproxy_build_cache');
        Storage::fake('imgproxy_cache');

        $this->artisan('yazar:clear-imgproxy-cache')
            ->assertExitCode(0)
            ->expectsOutputToContain('already empty');
    }
}
