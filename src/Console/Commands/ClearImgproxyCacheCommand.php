<?php

namespace Yazar\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearImgproxyCacheCommand extends Command
{
    protected $signature = 'yazar:clear-imgproxy-cache';

    protected $description = 'Delete all imgproxy images cached by ImgproxyBuildResolver during static build';

    public function handle(): int
    {
        $buildCacheFiles = Storage::disk('imgproxy_build_cache')->allFiles();
        $publishedFiles = Storage::disk('imgproxy_cache')->allFiles();

        if ($buildCacheFiles === [] && $publishedFiles === []) {
            $this->info('Imgproxy cache is already empty.');

            return self::SUCCESS;
        }

        Storage::disk('imgproxy_build_cache')->delete($buildCacheFiles);
        Storage::disk('imgproxy_cache')->delete($publishedFiles);

        $this->info((count($buildCacheFiles) + count($publishedFiles)).' cached imgproxy file(s) deleted.');

        return self::SUCCESS;
    }
}
