<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yazar\Build\BuildProfiler;
use Yazar\Models\Document;
use Yazar\Models\Post;

/**
 * Generates a synthetic content corpus and runs `build` with a profiler
 * attached, printing per-stage duration and count. Private dev tool — not
 * part of the seryak/yazar package, lives only inside this repository's
 * harness/ (see bin/harness-init.sh).
 */
class BenchmarkBuildCommand extends Command
{
    protected $signature = 'yazar:benchmark-build {--count=3000}';

    protected $description = 'Generate a synthetic content corpus and report build timings per stage';

    public function handle(): int
    {
        $count = (int) $this->option('count');

        // static_output is NOT cleared here: in this harness its disk root is
        // public_path('build') — the same directory Vite writes its compiled
        // manifest/assets into. Wiping it would delete the Vite build output
        // alongside generated pages. export/front_page counts below therefore
        // reflect files ADDED beyond whatever is already on static_output,
        // not a total — they under-report if a previous, larger run already
        // populated the disk.
        $this->info('Clearing content and imgproxy cache disks...');
        foreach (['content', 'imgproxy_build_cache', 'imgproxy_cache'] as $disk) {
            Storage::disk($disk)->delete(Storage::disk($disk)->allFiles());
        }

        $this->info("Generating {$count} synthetic documents...");
        (new SyntheticContentGenerator)->generate($count);

        $profiler = new CollectingBuildProfiler;
        app()->instance(BuildProfiler::class, $profiler);

        $this->call('build');

        $this->newLine();
        $profiler->report($this);

        // SyntheticContentGenerator writes $count posts plus a fixed 5 categories.
        $this->sanityCheck($count + 5);

        return self::SUCCESS;
    }

    private function sanityCheck(int $expectedDocuments): void
    {
        $this->newLine();

        $actualDocuments = DB::table(Document::TABLE)->count();
        if ($actualDocuments === $expectedDocuments) {
            $this->info("Sanity check: {$actualDocuments} documents in DB (expected {$expectedDocuments}).");
        } else {
            $this->warn("Sanity check MISMATCH: {$actualDocuments} documents in DB, expected {$expectedDocuments}.");
        }

        $exportedFiles = count(Storage::disk('static_output')->allFiles());
        $minimumExpectedFiles = Post::count();
        if ($exportedFiles >= $minimumExpectedFiles) {
            $this->info("Sanity check: {$exportedFiles} files on static_output (at least {$minimumExpectedFiles} expected from posts alone).");
        } else {
            $this->warn("Sanity check MISMATCH: only {$exportedFiles} files on static_output, expected at least {$minimumExpectedFiles} from posts alone.");
        }
    }
}
