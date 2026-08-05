<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Yazar\YazarServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [YazarServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('view.paths', array_merge(
            $app['config']->get('view.paths', []),
            [__DIR__.'/../stubs/views'],
        ));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
