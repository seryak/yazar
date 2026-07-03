<?php

namespace Yazar\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'yazar:install {--force : Overwrite existing files}';

    protected $description = 'Publish Yazar config, views and demo content';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->call('vendor:publish', ['--tag' => 'yazar-config', '--force' => $force]);
        $this->call('vendor:publish', ['--tag' => 'yazar-views', '--force' => $force]);
        $this->call('vendor:publish', ['--tag' => 'yazar-content', '--force' => $force]);

        $this->info('Yazar установлен. Дальше: php artisan migrate && php artisan build');

        return self::SUCCESS;
    }
}
