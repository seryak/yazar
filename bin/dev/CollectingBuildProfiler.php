<?php

namespace App\Console\Commands;

use Closure;
use Illuminate\Console\Command;
use Yazar\Build\BuildProfiler;

/**
 * Collects duration and count per build stage for the private
 * yazar:benchmark-build command. Not part of the seryak/yazar package —
 * lives only inside this repository's harness/ (see bin/harness-init.sh).
 */
class CollectingBuildProfiler implements BuildProfiler
{
    /** @var array<string, array{ms: float, count: int|null}> */
    private array $stages = [];

    public function stage(string $name, Closure $work): mixed
    {
        $start = hrtime(true);
        $result = $work();
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        $this->stages[$name] = [
            'ms' => $elapsedMs,
            'count' => is_int($result) ? $result : null,
        ];

        return $result;
    }

    public function report(Command $command): void
    {
        $rows = [];
        foreach ($this->stages as $name => $data) {
            $rows[] = [
                $name,
                number_format($data['ms'], 2),
                $data['count'] ?? '—',
            ];
        }

        $command->table(['Stage', 'Time, ms', 'Count'], $rows);
    }
}
