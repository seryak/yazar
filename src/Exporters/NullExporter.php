<?php

namespace Yazar\Exporters;

use Yazar\Contracts\Exporter;

class NullExporter implements Exporter
{
    /**
     * No constructor: BuildCommand always calls `new $exporterClass($modelClass)`
     * uniformly for every content type, but this class has no use for
     * $modelClass. PHP silently discards extra constructor arguments when no
     * `__construct()` is declared, so the call succeeds without one.
     */
    public function export(): void
    {
        // No-op: the owning model opts out of static export.
    }
}
