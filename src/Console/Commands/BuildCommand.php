<?php

namespace Yazar\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Yazar\Contracts\Documentable;
use Yazar\Documents\DocumentImportService;
use Yazar\Exporters\FrontPageExporter;
use Yazar\Models\Document;

class BuildCommand extends Command
{
    protected $signature = 'build';

    protected $description = 'Generate static build';

    public function handle(): int
    {
        Document::truncate();
        DocumentImportService::importAllConfiguredModels();

        foreach (config('yazar.content_types', []) as $modelClass) {
            $this->exportContentType($modelClass);
        }

        (new FrontPageExporter)->export();
        $this->move();

        $this->info('generating html pages is finish');

        return CommandAlias::SUCCESS;
    }

    /**
     * @param  class-string<Document&Documentable>  $modelClass
     */
    private function exportContentType(string $modelClass): void
    {
        $exporterClass = $modelClass::exporterClass();
        (new $exporterClass($modelClass))->export();
    }

    protected function move(): void
    {
        $deployTarget = config('yazar.deploy_target');

        if ($deployTarget === null) {
            return;
        }

        File::copyDirectory(Storage::disk('static_output')->path('/'), $deployTarget);
        File::copyDirectory(Storage::disk('public')->path('build'), $deployTarget.'/build');
    }
}
