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
     */
    public function reimportAll(): void
    {
        DB::table(Document::TABLE)->truncate();

        $this->importAll();
    }

    /**
     * Import only when nothing has been imported yet — cheap enough to call on
     * every request, which is how the dynamic routes bootstrap themselves.
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
     */
    public function importAll(): void
    {
        foreach (config('yazar.content_types', []) as $modelClass) {
            (new DocumentImportService($modelClass))->import();
        }
    }
}
