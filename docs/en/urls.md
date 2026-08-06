[← Templates](templates.md) · [Back to README](../../README.md) · [Configuration →](configuration.md)

# URLs and permalinks

## How a document's `url` is resolved

The `url` column is computed once, at import time, inside `DocumentImportService::persist()` — never at request or render time. Priority order:

1. **Front matter `url`** (non-empty string) — used verbatim. The model's `permalink()` pattern is not applied at all.
2. Otherwise, **the model's `permalink()` pattern**, with the `:slug` token filled from front matter `slug` (non-empty string), if present.
3. Otherwise, **the model's `permalink()` pattern**, with `:slug` filled from the filename (relative to the model's `documentsPath()`, `.md` stripped).

The result is stored with leading and trailing `/` trimmed (`trim($url, '/')`).

| Model | `permalink()` | File | Front matter | Resulting `url` |
|-------|----------------|------|---------------|------------------|
| `Page` | `/:slug` | `pages/about.md` | none | `about` |
| `Post` | `/blog/:slug` | `posts/2019-old-post.md` | `slug: old-post` | `blog/old-post` |
| `Post` | `/blog/:slug` | any | `url: archive/legacy` | `archive/legacy` — no `blog/` prefix, front matter `url` bypasses the pattern entirely |
| `Category` | `/:slug` | `categories/example.md` | none | `example` |

`permalink()` is defined per model (`Yazar\Contracts\Documentable::permalink()`) as a leading-slash string with `:token` placeholders. **Only `:slug` is implemented today.** The resolver itself is generic — `Yazar\Documents\PermalinkResolver::resolve()` substitutes every `:key` present in whatever `$tokens` array it's given:

```php
// src/Documents/PermalinkResolver.php
PermalinkResolver::resolve('/blog/:slug', ['slug' => 'hello-world']); // => 'blog/hello-world'
```

Adding a second token (e.g. `:category`) needs a change to the call site in `DocumentImportService::persist()`, which is the only place that builds the `$tokens` array — not to the resolver, which already handles an arbitrary token map.

## `url` is the single source of truth for both rendering modes

`documents.url` is the only thing both `ContentController` (dynamic HTTP routing — a plain `WHERE url = ?` lookup) and `Document::getPathForStaticPageAttribute()` (static build path) read to place a document. There's no separate URL computation for static vs. dynamic mode. A post reachable at `/blog/hello-world` over HTTP always builds to `blog/hello-world/index.html` (or `blog/hello-world.html` with `use_html_suffix` — see [Configuration](configuration.md)) on the `static_output` disk.

Routing is defined in `routes/web.php`:

```php
Route::get('/', [ContentController::class, 'renderMainPage'])->name('front-page');
Route::get('/{pageNumber}', [ContentController::class, 'renderMainPage'])->whereNumber('pageNumber');
Route::get('/{url}/{pageNumber}', [ContentController::class, 'showCategoryPage'])->where('url', '.+')->whereNumber('pageNumber');
Route::get('/{url}', [ContentController::class, 'show'])->where('url', '.+');
```

- `{url}` matches `.+`, so multi-segment URLs (e.g. `tools/jetbrains/test2`) work — the whole path after the domain is looked up as a single string against `documents.url`, not resolved segment by segment.
- A purely numeric top-level path (`/2`) matches the front-page pagination route (`{pageNumber}`) *before* the catch-all `{url}` route, since Laravel matches routes in declaration order. A document whose `url` happens to be numeric (e.g. a page imported with `slug: 2`) is unreachable at `/2` — it's shadowed by front-page pagination.
- `ContentController::show()` iterates `config('yazar.content_types')` in order and returns the first model whose table has a matching `url`. Since `url` is unique across all types (see below), at most one can ever match.

## Collisions

Before saving, `DocumentImportService::persist()` checks whether the computed `url` is already taken by *another* document — any type, not just the current one (the check queries the `documents` table directly, bypassing each model's per-type global scope):

- The **first** document processed to claim a `url` keeps it.
- Every later document that computes the same `url` is **skipped** — import continues with the rest of the batch instead of aborting.
- Skipped paths are collected into `url_conflicts` (`path => url`), kept separate from `invalid_documents` (reserved for front-matter/parsing errors) — a URL collision isn't a validation failure of the document itself.
- A unique constraint on `documents.url` at the database level is a second line of defense for races the pre-check misses; the resulting `QueryException` is caught and folded into the same `url_conflicts` rather than surfacing as an unhandled exception.

**The conflict report only surfaces during `php artisan build`.** `BuildCommand::handle()` prints it after import:

```
2 документов не получили уникальный url при импорте:
  - posts/duplicate.md: url 'blog/hello-world' уже занят другим документом
  - pages/dup2.md: url 'about' уже занят другим документом
```

That message is hardcoded in Russian in `BuildCommand::handle()`, regardless of the host application's own locale or the `language.*` settings in `.kodla/config.yaml` — it isn't translated per environment. The build still exits `0` — a collision is a warning, not a build failure.

The same collision happening through the dynamic-mode auto-import (`ImportEmptyContent` middleware → `ContentImporter::importIfEmpty()`) produces **no report at all** — `importIfEmpty()` discards its conflicts, since there's no console attached to a web request. The losing document simply doesn't resolve at any URL until the conflict is fixed and the content is re-imported.

## See also

- [Templates](templates.md) — the `slug`, `url`, and `category` front matter fields referenced above
- [Content types](content-types.md) — implementing `permalink()` on a new model
- [Configuration](configuration.md) — `use_html_suffix` and the `content_types` map
