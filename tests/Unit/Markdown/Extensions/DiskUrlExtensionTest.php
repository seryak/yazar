<?php

namespace Tests\Unit\Markdown\Extensions;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Tests\TestCase;
use Yazar\Markdown\Extensions\DiskUrlExtension;
use Yazar\Markdown\Extensions\DiskUrlResolutionException;

class DiskUrlExtensionTest extends TestCase
{
    private function convert(string $markdown): string
    {
        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new DiskUrlExtension);

        return (string) (new MarkdownConverter($environment))->convert($markdown);
    }

    /**
     * {@see DiskUrlExtension::resolve()}
     */
    public function test_resolves_disk_reference_inside_image(): void
    {
        Storage::fake('media', ['url' => 'https://cdn.test']);

        $html = $this->convert('![alt](disk(media)://photos/cat.png)');

        $this->assertStringContainsString('src="https://cdn.test/photos/cat.png"', $html);
    }

    /**
     * {@see DiskUrlExtension::resolve()}
     */
    public function test_resolves_disk_reference_inside_link(): void
    {
        Storage::fake('media', ['url' => 'https://cdn.test']);

        $html = $this->convert('[скачать](disk(media)://docs/report.pdf)');

        $this->assertStringContainsString('href="https://cdn.test/docs/report.pdf"', $html);
    }

    /**
     * {@see DiskUrlExtension::resolve()}
     */
    public function test_resolves_disk_reference_inside_plain_text(): void
    {
        Storage::fake('media', ['url' => 'https://cdn.test']);

        $html = $this->convert('Смотри тут disk(media)://photos/cat.png в тексте');

        $this->assertStringContainsString('Смотри тут https://cdn.test/photos/cat.png в тексте', $html);
    }

    /**
     * {@see DiskUrlExtension::resolve()}
     */
    public function test_resolves_disk_reference_inside_raw_html(): void
    {
        Storage::fake('media', ['url' => 'https://cdn.test']);

        $html = $this->convert('<img src="disk(media)://photos/cat.png">');

        $this->assertStringContainsString('src="https://cdn.test/photos/cat.png"', $html);
    }

    /**
     * {@see DiskUrlExtension::resolve()}
     */
    public function test_does_not_resolve_inside_fenced_code_block(): void
    {
        Storage::fake('media', ['url' => 'https://cdn.test']);

        $html = $this->convert("```\ndisk(media)://photos/cat.png\n```");

        $this->assertStringContainsString('disk(media)://photos/cat.png', $html);
        $this->assertStringNotContainsString('https://cdn.test', $html);
    }

    /**
     * {@see DiskUrlExtension::resolve()}
     */
    public function test_does_not_resolve_inside_inline_code(): void
    {
        Storage::fake('media', ['url' => 'https://cdn.test']);

        $html = $this->convert('`disk(media)://photos/cat.png`');

        $this->assertStringContainsString('disk(media)://photos/cat.png', $html);
        $this->assertStringNotContainsString('https://cdn.test', $html);
    }

    /**
     * {@see DiskUrlExtension::defaultDisk()}
     */
    public function test_resolves_disk_reference_without_explicit_disk_using_configured_default(): void
    {
        Storage::fake('media', ['url' => 'https://cdn.test']);
        config(['yazar.markdown.default_disk' => 'media']);

        $html = $this->convert('![alt](disk://images-posts/figma/dashed-line.gif)');

        $this->assertStringContainsString('src="https://cdn.test/images-posts/figma/dashed-line.gif"', $html);
    }

    /**
     * {@see DiskUrlExtension::defaultDisk()}
     */
    public function test_omitting_the_disk_without_a_configured_default_falls_back_to_the_filesystem_default_disk(): void
    {
        config(['yazar.markdown.default_disk' => null]);

        $html = $this->convert('![alt](disk://images-posts/figma/dashed-line.gif)');

        $this->assertStringContainsString('src="/storage/images-posts/figma/dashed-line.gif"', $html);
    }

    /**
     * {@see DiskUrlExtension::resolve()}
     */
    public function test_propagates_exception_for_unregistered_disk(): void
    {
        $this->expectException(DiskUrlResolutionException::class);

        $this->convert('![alt](disk(unregistered)://photos/cat.png)');
    }

    /**
     * {@see DiskUrlExtension::resolve()}
     */
    public function test_wraps_the_original_exception_as_previous(): void
    {
        try {
            $this->convert('![alt](disk(unregistered)://photos/cat.png)');
            $this->fail('Expected DiskUrlResolutionException was not thrown.');
        } catch (DiskUrlResolutionException $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
        }
    }
}
