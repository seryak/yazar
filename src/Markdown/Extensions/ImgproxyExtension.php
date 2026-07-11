<?php

namespace Yazar\Markdown\Extensions;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\NodeIterator;
use Throwable;

class ImgproxyExtension implements ExtensionInterface
{
    private const PATTERN = '/imgproxy\(([^,]+),\s*\'([\w-]+)\'\)/';

    private const DISK_PATTERN = '/^disk(?:\(([\w.-]+)\))?:\/\/(.+)$/';

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, $this->resolve(...));
    }

    public function resolve(DocumentParsedEvent $event): void
    {
        foreach (new NodeIterator($event->getDocument()) as $node) {
            match (true) {
                $node instanceof Link, $node instanceof Image => $node->setUrl(self::replace($node->getUrl())),
                $node instanceof Text, $node instanceof HtmlInline, $node instanceof HtmlBlock => $node->setLiteral(self::replace($node->getLiteral())),
                default => null,
            };
        }
    }

    public static function url(string $source, string $presetKey): string
    {
        $resolvedSource = self::resolveSource(trim($source));
        $options = self::preset($presetKey);
        $path = "{$options}/plain/{$resolvedSource}";

        return rtrim(self::baseUrl(), '/').'/'.self::sign($path).'/'.$path;
    }

    private static function replace(string $value): string
    {
        return preg_replace_callback(
            self::PATTERN,
            static fn (array $matches): string => self::url($matches[1], $matches[2]),
            $value,
        ) ?? $value;
    }

    private static function resolveSource(string $source): string
    {
        if (! preg_match(self::DISK_PATTERN, $source, $matches)) {
            return $source;
        }

        $disk = $matches[1] !== '' ? $matches[1] : self::defaultDisk();
        $path = ltrim($matches[2], '/');

        try {
            $config = config("filesystems.disks.{$disk}");
            if (is_array($config) && ($config['driver'] ?? null) === 's3') {
                return self::s3Source($config, $path);
            }

            return Storage::disk($disk)->url($path);
        } catch (Throwable $e) {
            throw new ImgproxyResolutionException(
                "Unable to resolve source disk({$disk})://{$path}: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function s3Source(array $config, string $path): string
    {
        $bucket = $config['bucket'] ?? null;
        if (! is_string($bucket) || $bucket === '') {
            throw new InvalidArgumentException('S3 disk is missing a "bucket" config value.');
        }

        $root = is_string($config['root'] ?? null) ? trim($config['root'], '/') : '';

        return $root !== '' ? "s3://{$bucket}/{$root}/{$path}" : "s3://{$bucket}/{$path}";
    }

    private static function preset(string $key): string
    {
        $preset = config("yazar.imgproxy.presets.{$key}");

        if (! is_string($preset) || $preset === '') {
            throw new ImgproxyResolutionException("Unknown imgproxy preset: {$key}");
        }

        return $preset;
    }

    private static function sign(string $path): string
    {
        $keyHex = config('yazar.imgproxy.key');
        $saltHex = config('yazar.imgproxy.salt');

        if (! is_string($keyHex) || ! is_string($saltHex) || $keyHex === '' || $saltHex === '') {
            throw new ImgproxyResolutionException('Imgproxy signing key/salt are not configured.');
        }

        if (! self::isHex($keyHex) || ! self::isHex($saltHex)) {
            throw new ImgproxyResolutionException('Imgproxy signing key/salt are not valid hex strings.');
        }

        $key = hex2bin($keyHex);
        $salt = hex2bin($saltHex);
        if ($key === false || $salt === false) {
            throw new ImgproxyResolutionException('Imgproxy signing key/salt are not valid hex strings.');
        }

        $signature = hash_hmac('sha256', $salt.'/'.$path, $key, true);

        return rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }

    private static function isHex(string $value): bool
    {
        return ctype_xdigit($value) && strlen($value) % 2 === 0;
    }

    private static function baseUrl(): string
    {
        $baseUrl = config('yazar.imgproxy.base_url');

        return is_string($baseUrl) ? $baseUrl : 'http://127.0.0.1:6066';
    }

    private static function defaultDisk(): ?string
    {
        $disk = config('yazar.markdown.default_disk');
        if (is_string($disk)) {
            return $disk;
        }

        $default = config('filesystems.default');

        return is_string($default) ? $default : null;
    }
}
