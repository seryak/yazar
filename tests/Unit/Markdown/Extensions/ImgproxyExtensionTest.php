<?php

namespace Tests\Unit\Markdown\Extensions;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yazar\Markdown\Extensions\DiskUrlExtension;
use Yazar\Markdown\Extensions\ImgproxyExtension;
use Yazar\Markdown\Extensions\ImgproxyResolutionException;

#[CoversClass(ImgproxyExtension::class)]
class ImgproxyExtensionTest extends TestCase
{
    private const KEY_HEX = '68656c6c6f6b6579';

    private const SALT_HEX = '776f726c6473616c74';

    private function convert(string $markdown): string
    {
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new ImgproxyExtension);

        return (string) (new MarkdownConverter($environment))->convert($markdown);
    }

    private function expectedSignature(string $path): string
    {
        $key = hex2bin(self::KEY_HEX);
        $salt = hex2bin(self::SALT_HEX);
        $signature = hash_hmac('sha256', $salt.'/'.$path, $key, true);

        return rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }

    /**
     * {@see ImgproxyExtension::resolveSource()}
     */
    #[TestDox('resolveSource() resolves a bare URL source as is')]
    public function test_resolves_bare_url_source_as_is(): void
    {
        config([
            'yazar.imgproxy.key' => self::KEY_HEX,
            'yazar.imgproxy.salt' => self::SALT_HEX,
            'yazar.imgproxy.presets.preset' => 'rs:fit:300:300',
        ]);

        $html = $this->convert("imgproxy(https://example.com/photo.jpg, 'preset')");

        $path = 'rs:fit:300:300/plain/https://example.com/photo.jpg';
        $expected = 'http://127.0.0.1:6066/'.$this->expectedSignature($path).'/'.$path;

        $this->assertStringContainsString($expected, $html);
    }

    /**
     * {@see ImgproxyExtension::resolveSource()}
     */
    #[TestDox('resolveSource() resolves a disk reference with the local driver via the storage URL')]
    public function test_resolves_disk_reference_with_local_driver_via_storage_url(): void
    {
        Storage::fake('media', ['url' => 'https://cdn.test']);
        config([
            'yazar.imgproxy.key' => self::KEY_HEX,
            'yazar.imgproxy.salt' => self::SALT_HEX,
            'yazar.imgproxy.presets.preset' => 'rs:fit:300:300',
        ]);

        $html = $this->convert("imgproxy(disk(media)://photos/cat.png, 'preset')");

        $this->assertStringContainsString('/plain/https://cdn.test/photos/cat.png', $html);
    }

    /**
     * {@see ImgproxyExtension::s3Source()}
     */
    #[TestDox('s3Source() resolves a disk reference with the s3 driver without creating a Flysystem adapter')]
    public function test_resolves_disk_reference_with_s3_driver_without_creating_a_flysystem_adapter(): void
    {
        config([
            'filesystems.disks.s3disk' => ['driver' => 's3', 'bucket' => 'test-bucket', 'root' => 'prefix'],
            'yazar.imgproxy.key' => self::KEY_HEX,
            'yazar.imgproxy.salt' => self::SALT_HEX,
            'yazar.imgproxy.presets.preset' => 'rs:fit:300:300',
        ]);

        $html = $this->convert("imgproxy(disk(s3disk)://photos/cat.png, 'preset')");

        $this->assertStringContainsString('/plain/s3://test-bucket/prefix/photos/cat.png', $html);
    }

    /**
     * {@see ImgproxyExtension::resolveSource()}
     */
    #[TestDox('resolveSource() strips a leading slash from the path to avoid a double slash in the s3 source')]
    public function test_strips_a_leading_slash_from_the_path_to_avoid_a_double_slash_in_the_s3_source(): void
    {
        config([
            'filesystems.disks.s3disk' => ['driver' => 's3', 'bucket' => 'test-bucket', 'root' => 'prefix'],
            'yazar.imgproxy.key' => self::KEY_HEX,
            'yazar.imgproxy.salt' => self::SALT_HEX,
            'yazar.imgproxy.presets.preset' => 'rs:fit:300:300',
        ]);

        $html = imgproxy('disk(s3disk)://'.'/photos/cat.png', 'preset');

        $this->assertStringContainsString('/plain/s3://test-bucket/prefix/photos/cat.png', $html);
        $this->assertStringNotContainsString('prefix//photos', $html);
    }

    /**
     * {@see ImgproxyExtension::sign()}
     */
    #[TestDox('sign() signs the URL with the configured key and salt')]
    public function test_signs_the_url_with_the_configured_key_and_salt(): void
    {
        config([
            'yazar.imgproxy.key' => self::KEY_HEX,
            'yazar.imgproxy.salt' => self::SALT_HEX,
            'yazar.imgproxy.presets.preset' => 'q:80',
        ]);

        $html = $this->convert("imgproxy(https://example.com/photo.jpg, 'preset')");

        $path = 'q:80/plain/https://example.com/photo.jpg';
        $this->assertStringContainsString('/'.$this->expectedSignature($path).'/'.$path, $html);
    }

    /**
     * {@see ImgproxyExtension::preset()}
     */
    #[TestDox('preset() throws for an unknown preset')]
    public function test_throws_for_unknown_preset(): void
    {
        config([
            'yazar.imgproxy.key' => self::KEY_HEX,
            'yazar.imgproxy.salt' => self::SALT_HEX,
        ]);

        $this->expectException(ImgproxyResolutionException::class);

        $this->convert("imgproxy(https://example.com/photo.jpg, 'unknown-preset')");
    }

    /**
     * {@see ImgproxyExtension::resolveSource()}
     */
    #[TestDox('resolveSource() throws for an unregistered disk reference')]
    public function test_throws_for_unregistered_disk_reference(): void
    {
        config([
            'yazar.imgproxy.key' => self::KEY_HEX,
            'yazar.imgproxy.salt' => self::SALT_HEX,
            'yazar.imgproxy.presets.preset' => 'q:80',
        ]);

        try {
            $this->convert("imgproxy(disk(unregistered)://photos/cat.png, 'preset')");
            $this->fail('Expected ImgproxyResolutionException was not thrown.');
        } catch (ImgproxyResolutionException $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
        }
    }

    /**
     * {@see ImgproxyExtension::sign()}
     */
    #[TestDox('sign() throws when key or salt are not configured')]
    public function test_throws_when_key_or_salt_are_not_configured(): void
    {
        config([
            'yazar.imgproxy.key' => null,
            'yazar.imgproxy.salt' => null,
            'yazar.imgproxy.presets.preset' => 'q:80',
        ]);

        $this->expectException(ImgproxyResolutionException::class);

        $this->convert("imgproxy(https://example.com/photo.jpg, 'preset')");
    }

    /**
     * {@see ImgproxyExtension::sign()}
     */
    #[TestDox('sign() throws when key or salt are not valid hex strings')]
    public function test_throws_when_key_or_salt_are_not_valid_hex_strings(): void
    {
        config([
            'yazar.imgproxy.key' => 'not-a-hex-string',
            'yazar.imgproxy.salt' => self::SALT_HEX,
            'yazar.imgproxy.presets.preset' => 'q:80',
        ]);

        $this->expectException(ImgproxyResolutionException::class);

        $this->convert("imgproxy(https://example.com/photo.jpg, 'preset')");
    }

    /**
     * {@see ImgproxyExtension::resolve()}
     */
    #[TestDox('resolve() does not resolve inside a fenced code block')]
    public function test_does_not_resolve_inside_fenced_code_block(): void
    {
        $html = $this->convert("```\nimgproxy(https://example.com/photo.jpg, 'preset')\n```");

        $this->assertStringContainsString("imgproxy(https://example.com/photo.jpg, 'preset')", $html);
        $this->assertStringNotContainsString('127.0.0.1:6066', $html);
    }

    /**
     * {@see ImgproxyExtension::resolve()}
     */
    #[TestDox('resolve() does not resolve inside inline code')]
    public function test_does_not_resolve_inside_inline_code(): void
    {
        $html = $this->convert("`imgproxy(https://example.com/photo.jpg, 'preset')`");

        $this->assertStringContainsString("imgproxy(https://example.com/photo.jpg, 'preset')", $html);
        $this->assertStringNotContainsString('127.0.0.1:6066', $html);
    }

    /**
     * {@see ImgproxyExtension::resolveSource()}
     */
    #[TestDox('resolveSource() resolves correctly when registered before DiskUrlExtension')]
    public function test_resolves_correctly_when_registered_before_disk_url_extension(): void
    {
        Storage::fake('media', ['url' => 'https://cdn.test']);
        config([
            'yazar.imgproxy.key' => self::KEY_HEX,
            'yazar.imgproxy.salt' => self::SALT_HEX,
            'yazar.imgproxy.presets.preset' => 'rs:fit:300:300',
        ]);

        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new ImgproxyExtension);
        $environment->addExtension(new DiskUrlExtension);

        $html = (string) (new MarkdownConverter($environment))->convert("imgproxy(disk(media)://photos/cat.png, 'preset')");

        $this->assertStringContainsString('/plain/https://cdn.test/photos/cat.png', $html);
    }

    /**
     * {@see ImgproxyExtension::url()}
     */
    #[TestDox('url() builds the same signed link outside of markdown')]
    public function test_url_builds_the_same_signed_link_outside_of_markdown(): void
    {
        Storage::fake('media', ['url' => 'https://cdn.test']);
        config([
            'yazar.imgproxy.key' => self::KEY_HEX,
            'yazar.imgproxy.salt' => self::SALT_HEX,
            'yazar.imgproxy.presets.preset' => 'rs:fit:300:300',
        ]);

        $viaHelper = imgproxy('disk(media)://photos/cat.png', 'preset');
        $viaMarkdown = $this->convert("imgproxy(disk(media)://photos/cat.png, 'preset')");

        $this->assertStringContainsString($viaHelper, $viaMarkdown);
    }
}
