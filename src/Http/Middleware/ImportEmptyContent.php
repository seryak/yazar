<?php

namespace Yazar\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yazar\Documents\ContentImporter;

class ImportEmptyContent
{
    public function __construct(
        private readonly ContentImporter $importer,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->importer->importIfEmpty();

        return $next($request);
    }
}
