<?php

namespace Yazar;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterParserInterface;
use League\CommonMark\MarkdownConverter;
use Yazar\Build\BuildProfiler;
use Yazar\Build\NullBuildProfiler;
use Yazar\Console\Commands\BuildCommand;
use Yazar\Console\Commands\ClearImgproxyCacheCommand;
use Yazar\Console\Commands\InstallCommand;
use Yazar\Markdown\MarkdownParser;

class YazarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/yazar.php', 'yazar');

        $this->app->bind(BuildProfiler::class, NullBuildProfiler::class);

        $this->registerMarkdown();
    }

    private function registerMarkdown(): void
    {
        $this->app->singleton(FrontMatterExtension::class);

        $this->app->singleton(FrontMatterParserInterface::class, fn (Application $app): FrontMatterParserInterface => $app->make(FrontMatterExtension::class)->getFrontMatterParser());

        $this->app->singleton(MarkdownConverter::class, function (Application $app): MarkdownConverter {
            $environment = new Environment;
            $environment->addExtension(new CommonMarkCoreExtension);
            $environment->addExtension($app->make(FrontMatterExtension::class));

            foreach (config('yazar.markdown.extensions', []) as $extensionClass) {
                $environment->addExtension($app->make($extensionClass));
            }

            return new MarkdownConverter($environment);
        });

        $this->app->bind(MarkdownParser::class, fn (Application $app): MarkdownParser => new MarkdownParser(
            $app->make(MarkdownConverter::class),
            $app->make(FrontMatterParserInterface::class),
        ));
    }

    public function boot(): void
    {
        foreach (config('yazar.disks', []) as $name => $diskConfig) {
            config(["filesystems.disks.$name" => $diskConfig]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/yazar.php' => config_path('yazar.php'),
            ], 'yazar-config');

            $this->publishes([
                __DIR__.'/../stubs/views' => resource_path('views'),
            ], 'yazar-views');

            $this->publishes([
                __DIR__.'/../stubs/content' => base_path('_content'),
            ], 'yazar-content');

            $this->commands([
                BuildCommand::class,
                InstallCommand::class,
                ClearImgproxyCacheCommand::class,
            ]);
        }
    }
}
