<?php

namespace Yazar\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yazar\Documents\DocumentImportService;
use Yazar\Models\Document;

class ImportEmptyContent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Document::count() === 0) {
            DocumentImportService::importAllConfiguredDisks();
        }

        return $next($request);
    }
}
