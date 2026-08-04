<?php

namespace Yazar\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Yazar\Build\BuildProfiler;
use Yazar\Build\ImgproxyBuildResolver;
use Yazar\Documents\ContentImporter;
use Yazar\Exporters\FrontPageExporter;
use Yazar\Models\Document;

class BuildCommand extends Command
{
    protected $signature = 'build';

    protected $description = 'Generate static build';

    public function handle(ContentImporter $importer, BuildProfiler $profiler): int
    {
        $profiler->stage('import', function () use ($importer) {
            $importer->reimportAll();

            return DB::table(Document::TABLE)->count();
        });

        $profiler->stage('export', function () {
            foreach (config('yazar.content_types', []) as $modelClass) {
                $this->exportContentType($modelClass);
            }

            return count(Storage::disk('static_output')->allFiles());
        });

        $profiler->stage('front_page', function () {
            $before = count(Storage::disk('static_output')->allFiles());
            (new FrontPageExporter)->export();

            return count(Storage::disk('static_output')->allFiles()) - $before;
        });

        $imgproxyResolver = new ImgproxyBuildResolver;

        $failures = $profiler->stage('imgproxy_resolve', fn () => $imgproxyResolver->resolve());
        if ($failures !== []) {
            $this->newLine();
            $this->warn(count($failures).' imgproxy-ссылок не удалось скачать при сборке:');
            foreach ($failures as $url => $reason) {
                $this->line("  - {$url}: {$reason}");
            }
        }

        $profiler->stage('imgproxy_publish', function () use ($imgproxyResolver) {
            $before = count(Storage::disk('imgproxy_cache')->allFiles());
            $imgproxyResolver->publish();

            return count(Storage::disk('imgproxy_cache')->allFiles()) - $before;
        });

        $profiler->stage('move', function () {
            $this->move();

            return null;
        });

        $this->info('generating html pages is finish');

        return CommandAlias::SUCCESS;
    }

    /**
     * @param  class-string<Document>  $modelClass
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
