<?php

namespace Yazar\Build;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImgproxyBuildResolver
{
    private const DOWNLOAD_TIMEOUT_SECONDS = 10;

    private const UNKNOWN_PRESET_SEGMENT = 'unknown';

    /** @var array<string, string|null> url => href on static_output (null = could not be resolved) */
    private array $resolvedByUrl = [];

    /** @var array<string, string> url => failure reason */
    private array $failures = [];

    /**
     * @return array<string, string> url => failure reason (empty if everything downloaded)
     */
    public function resolve(): array
    {
        $this->resolvedByUrl = [];
        $this->failures = [];

        $baseUrl = rtrim((string) config('yazar.imgproxy.base_url'), '/');
        if ($baseUrl === '') {
            return [];
        }

        $pattern = '/'.preg_quote($baseUrl, '/').'\/[^\s"\'<>]+/';

        foreach (Storage::disk('static_output')->allFiles() as $path) {
            $original = (string) Storage::disk('static_output')->get($path);
            $replaced = preg_replace_callback(
                $pattern,
                fn (array $matches): string => $this->resolveUrl($baseUrl, $matches[0]) ?? $matches[0],
                $original,
            ) ?? $original;

            if ($replaced !== $original) {
                Storage::disk('static_output')->put($path, $replaced);
            }
        }

        return $this->failures;
    }

    /**
     * Copies every file downloaded into the local `imgproxy_build_cache` staging disk onto the
     * `imgproxy_cache` disk — the one that actually serves the images (local `public/`, S3, or
     * anything else the host application configures). Files already present on `imgproxy_cache`
     * are left untouched, so re-running this after a build that downloaded nothing new is a no-op.
     */
    public function publish(): void
    {
        foreach (Storage::disk('imgproxy_build_cache')->allFiles() as $path) {
            if (Storage::disk('imgproxy_cache')->exists($path)) {
                continue;
            }

            Storage::disk('imgproxy_cache')->put($path, (string) Storage::disk('imgproxy_build_cache')->get($path));
        }
    }

    private function resolveUrl(string $baseUrl, string $url): ?string
    {
        if (array_key_exists($url, $this->resolvedByUrl)) {
            return $this->resolvedByUrl[$url];
        }

        $targetPath = $this->targetPath($baseUrl, $url);

        if (Storage::disk('imgproxy_build_cache')->exists($targetPath)) {
            return $this->resolvedByUrl[$url] = Storage::disk('imgproxy_cache')->url($targetPath);
        }

        try {
            $response = Http::timeout(self::DOWNLOAD_TIMEOUT_SECONDS)->get($url);
        } catch (ConnectionException $e) {
            $this->failures[$url] = $e->getMessage();

            return $this->resolvedByUrl[$url] = null;
        }

        if ($response->failed()) {
            $this->failures[$url] = "HTTP {$response->status()}";

            return $this->resolvedByUrl[$url] = null;
        }

        Storage::disk('imgproxy_build_cache')->put($targetPath, $response->body());

        return $this->resolvedByUrl[$url] = Storage::disk('imgproxy_cache')->url($targetPath);
    }

    /**
     * Builds a path of the form `{preset name}/{original filename}`.
     */
    private function targetPath(string $baseUrl, string $url): string
    {
        $parts = explode('/plain/', $url, 2);
        if (count($parts) !== 2) {
            return sha1($url);
        }

        [$beforePlain, $sourcePart] = $parts;
        $preset = $this->presetKeyFromUrl($baseUrl, $beforePlain) ?? self::UNKNOWN_PRESET_SEGMENT;
        $filename = basename((string) (parse_url($sourcePart, PHP_URL_PATH) ?? $sourcePart));

        return $preset.'/'.($filename !== '' ? $filename : sha1($url));
    }

    /**
     * Reverse-looks-up the preset key from the options string embedded in the URL — ImgproxyExtension
     * only stores the expanded options (`config('yazar.imgproxy.presets.<key>')`) in the signed link,
     * not the original key, so the key has to be matched against the current preset config.
     */
    private function presetKeyFromUrl(string $baseUrl, string $beforePlain): ?string
    {
        if (! str_starts_with($beforePlain, $baseUrl.'/')) {
            return null;
        }

        // $beforePlain = "{base_url}/{signature}/{options}" — strip off "{base_url}/{signature}/".
        $afterBase = substr($beforePlain, strlen($baseUrl) + 1);
        $slashPos = strpos($afterBase, '/');
        if ($slashPos === false) {
            return null;
        }

        $options = substr($afterBase, $slashPos + 1);
        $presets = config('yazar.imgproxy.presets', []);
        if (! is_array($presets)) {
            return null;
        }

        $key = array_search($options, $presets, true);

        return is_string($key) ? $key : null;
    }
}
