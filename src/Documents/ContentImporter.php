<?php

namespace Yazar\Documents;

use Illuminate\Support\Facades\DB;
use Yazar\Models\Document;

/**
 * Orchestrates importing every configured content type.
 *
 * DocumentImportService handles a single model; this class owns the
 * whole-corpus lifecycle — including wiping the table first — so callers never
 * have to reach past the model layer themselves. Concrete Document models are
 * always scoped to their own type, and the base model is abstract, so the
 * cross-type reset and emptiness check live here rather than on a model.
 */
class ContentImporter
{
    /**
     * Drop every stored document, then import all configured content types
     * from scratch. Used by the static build, where stale rows must not leak
     * into the generated output.
     *
     * @return array<string, string> path => url that was already taken by another document
     */
    public function reimportAll(): array
    {
        DB::table(Document::TABLE)->truncate();

        return $this->importAll();
    }

    /**
     * Import only when nothing has been imported yet — cheap enough to call on
     * every request, which is how the dynamic routes bootstrap themselves. The
     * url-conflict report only matters for the static build, so it's discarded
     * here rather than surfaced to a request.
     */
    public function importIfEmpty(): void
    {
        if (DB::table(Document::TABLE)->exists()) {
            return;
        }

        $this->importAll();
    }

    /**
     * Import every model listed in `yazar.content_types`, leaving existing rows
     * in place (DocumentImportService upserts on path + type).
     *
     * @return array<string, string> path => url that was already taken by another document
     */
    public function importAll(): array
    {
        $conflicts = [];

        foreach (config('yazar.content_types', []) as $modelClass) {
            $result = (new DocumentImportService($modelClass))->import();
            $conflicts += $result['url_conflicts'];
        }

        return $conflicts;
    }
}
