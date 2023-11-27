<?php

namespace Tests\Unit\App\Models\Page;

use App\Models\Yazar\PageDocument;
use Tests\TestCase;

/**
 * {@see PageDocument::__construct()}
 */
class Constructor extends TestCase
{
    protected const FILEPATH = 'test-test.md';
    protected const VIEWPATH = 'views/test-test.blade.php';

    public function testOk(): void
    {
        $fileContent = <<<EOT
        ---
        view::extends: test-test
        view::section: content
        title: test
        created_at : 2022-05-06
        description: test description
        cover_image: /assets/post_covers/test.png
        ---
        olololo
        EOT;

        $this->mockFile($fileContent);
        $this->mockView();
        $page = new PageDocument(content_path(self::FILEPATH));

        $this->assertEquals('test-test', $page->view);
        $this->assertEquals('test-test', $page->fileName);
        $this->assertEquals('test', $page->title);
        $this->assertEquals('<h1>test</h1><div><p>olololo</p>'.PHP_EOL.'</div>', $page->fileHtml);
        $this->assertEquals('<p>olololo</p>'.PHP_EOL, $page->htmlContent);
    }

    public function testWrongFile()
    {
        $this->expectExceptionMessage('file_get_contents(wrong): Failed to open stream: No such file or directory');
        new PageDocument('wrong');
    }

    public function testWrongView()
    {
        $fileContent = <<<EOT
        ---
        view::extends: test-test
        view::section: content
        title: test
        created_at : 2022-05-06
        description: test description
        cover_image: /assets/post_covers/test.png
        ---
        olololo
        EOT;

        $this->mockFile($fileContent);
        $this->expectExceptionMessage('View [test-test] not found.');
        $page = new PageDocument(content_path(self::FILEPATH));
    }

    protected function mockFile(string $content, bool $create = true): void
    {
        if ($create) {
            file_put_contents(content_path(self::FILEPATH), $content);
        }
    }

    protected function mockView(): void
    {
        $viewString = '<h1>{{$page->title}}</h1><div>{!! $page->htmlContent !!}</div>';
        file_put_contents(resource_path(self::VIEWPATH), $viewString);
    }

    public function tearDown(): void
    {
        if (file_exists(resource_path(self::VIEWPATH))) {
            unlink(resource_path(self::VIEWPATH));
        }
        if (file_exists(content_path(self::FILEPATH))) {
            unlink(content_path(self::FILEPATH));
        }
        parent::tearDown();
    }
}
