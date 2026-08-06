[← Configuration](configuration.md) · [Back to README](../../README.md) · [Harness →](harness.md)

# Adding a new content type

## Overview

A content type is a single Eloquent model extending the abstract `Yazar\Models\Document`, implementing `Yazar\Contracts\Documentable`, paired with a class implementing `Yazar\Contracts\Exporter` for static builds, and registered under a key in `config('yazar.content_types')`. All content types share one `documents` database table (see [Configuration](configuration.md)) and one Markdown-import pipeline (`DocumentImportService`) — adding a type needs no new migration, no new controller, no new import code.

Ready-made building blocks to copy from: `Post`, `Page`, `Category` in `src/Models/`, and their exporters (`PostExporter`, `PageExporter`, `CategoryExporter`, plus `NullExporter` for types that opt out of static export entirely) in `src/Exporters/`.

## 1. Implement the model

```php
namespace App\Models; // anywhere autoloaded in the host app — a content type doesn't need to live in the package

use Yazar\Exporters\NullExporter; // or a custom exporter, see step 2
use Yazar\Models\Document;

class Note extends Document
{
    public static function documentType(): string
    {
        return 'note'; // stored in documents.type; Document::booted() enforces this on save
    }

    public static function documentsPath(): string
    {
        return 'notes'; // subfolder on the `content` disk: {content_path}/notes/*.md
    }

    public static function exporterClass(): string
    {
        return NullExporter::class;
    }

    public static function permalink(): string
    {
        return '/notes/:slug'; // see Urls — only :slug is implemented today
    }
}
```

All four static methods come from `Yazar\Contracts\Documentable` (`src/Contracts/Documentable.php`) and are required — `Document` itself is `abstract` and can't be instantiated directly. `documentType()` is enforced at save time: `Document::booted()` throws `InvalidArgumentException` if you try to assign a `type` attribute that disagrees with it, and a global scope (`document_type`) transparently filters every query on the model to rows of that type — `Note::all()` never sees a `Post` row even though they share the `documents` table.

If the type needs its own relations (the way `Category::posts()` does), add them the normal Eloquent way — beyond the `Documentable` contract, `Note` is just a model.

## 2. Implement (or reuse) an exporter

Every exporter implements `Yazar\Contracts\Exporter::export(): void`, called by `BuildCommand` as `new $exporterClass($modelClass)`. Three existing shapes to copy from:

- **No static output at all** — reuse `Yazar\Exporters\NullExporter`, for a type that only ever renders dynamically. It declares no constructor; `BuildCommand` always passes `$modelClass` as an argument, but PHP silently drops it when the class has no `__construct()`. None of the three built-in types (`Post`, `Page`, `Category`) currently use it — it exists as a ready-made opt-out for types that don't need one.
- **One file per document** — copy `PostExporter`/`PageExporter`: iterate `$modelClass::all()`, render `$document->meta->viewExtends` with `['page' => $document]`, write the result to `Storage::disk('static_output')->put($document->path_for_static_page, $html)`.
- **A paginated listing per document** (like `Category`) — copy `CategoryExporter`: build a `Yazar\Support\Paginator` and iterate `->pages()`, writing each page's `->path()`.

Whatever variables your exporter passes to `view()` must match what the dynamic route passes for the same view — see [Templates](templates.md) for the exact shape `ContentController` uses, including the caveat about attributes (`previousPage`/`nextPage`/`category`) that only exist in dynamic rendering.

## 3. Register the type

```php
// config/yazar.php
'content_types' => [
    'posts' => Post::class,
    'pages' => Page::class,
    'categories' => Category::class,
    'notes' => Note::class, // new
],
```

The key is iterated generically everywhere that matters (`DocumentImportService`/`ContentImporter`, `BuildCommand::exportContentType()`, `ContentController::show()`) — any key name works for a brand-new type. **Exception:** `posts` and `categories` are additionally read by fixed key in three spots inside `ContentController` (`config('yazar.content_types.posts')` in `renderMainPage()`/`renderDocument()`, `config('yazar.content_types.categories')` in `showCategoryPage()`/`renderDocument()`). Adding `notes` doesn't touch any of that — but renaming `posts` or `categories` themselves would require updating those call sites too.

## 4. Add content and a view

Create `{content_path}/notes/` (matching `documentsPath()`) and drop a Markdown file in it:

```markdown
---
view::extends: note
title: First note
created_at: "2026-01-01"
---

Note body goes here.
```

`view::extends: note` means a `note` Blade view must exist wherever the host application's view paths resolve it — `DocumentImportService` rejects the document at import time otherwise (the `view()->exists()` check inside `isValidOptions()`). A minimal view:

```blade
@extends('layout')

@section('main')
    <h1>{{ $page->meta->title }}</h1>
    {!! $page->html_content !!}
@endsection
```

## 5. Import and verify

```bash
php artisan migrate   # only needed once per project — no new migration for a new type
php artisan build     # or let ImportEmptyContent auto-import on the next request in dynamic mode
```

No new migration is needed: `notes` land in the same `documents` table as every other type, distinguished only by the `type` column and the global scope declared on `Note`.

## See also

- [Templates](templates.md) — front matter fields and view variables a new type's Blade views can rely on
- [Urls](urls.md) — how `permalink()` feeds into the final `url`, and what happens on a collision
- [Configuration](configuration.md) — the full shape of `content_types` and the shared `content` disk
