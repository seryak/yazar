[Back to README](../../README.md) · [Urls →](urls.md)

# Templates and front matter

Every Markdown file has two parts: a YAML front matter block (delimited by `---` lines at the top, parsed by League CommonMark's `FrontMatterParserInterface`) and the Markdown body below it. Front matter becomes `$document->meta` — a `Yazar\ValueObjects\DocumentMeta` object; the body becomes `$document->content`/`$document->html_content`.

## Front matter fields

**Required** — validated by `DocumentImportService::isValidOptions()` before import; a file missing any of these, or failing its rule, is skipped and reported in `invalid_documents` instead of being imported:

| Field | Type | Purpose |
|-------|------|---------|
| `view::extends` | string | Blade view name that renders this document. Must exist (`view()->exists()`) or the document is rejected at import time. |
| `title` | string | Document title, exposed via `$meta->title`. |
| `created_at` | string, parsable as a date | Stored as `published_at` (`Carbon::parse()`), drives ordering on the front page and in categories. |

Note the `::` in `view::extends` — it's a literal part of the front matter key, not Yazar-specific YAML syntax. It just happens to be a valid YAML mapping key.

**Recognized optional fields:**

| Field | Type | Purpose |
|-------|------|---------|
| `slug` | string | Feeds the `:slug` token when resolving the document's `url`. Falls back to the filename (without `.md`) when absent — see [Urls](urls.md). |
| `url` | string | Overrides the resolved `url` outright, bypassing the model's `permalink()` pattern entirely — see [Urls](urls.md). |
| `category` | string | A `Category` document's `slug`. Only meaningful on `Post` documents: `Category::posts()` is a `hasMany` keyed on `meta->category = slug`. Not validated against existing categories at import time — a typo silently yields no matching category. |

**Anything else** you put in front matter (e.g. `description` and `cover_image` in the stub theme's demo content) is stored as-is and reachable through `$meta->yourField`. `DocumentMeta` keeps every key it doesn't recognize in an internal `extra` array and returns it via `__get()`/`get()` (`src/ValueObjects/DocumentMeta.php`). There is no fixed schema beyond the three required fields above — a new piece of metadata needs no code change, just a new front matter key and a matching `{{ $page->meta->key }}` in the view.

## Blade template variables

The variables a view receives depend on which kind of page is rendering, and are the same in dynamic (`ContentController`) and static (`Exporters\*`) rendering — with one exception noted below.

| Rendering | Variables passed | Set by |
|-----------|-------------------|--------|
| A page/post document view (whatever `view::extends` names) | `$page` — the `Page`/`Post` model itself | `ContentController::renderDocument()` (dynamic), `PageExporter`/`PostExporter::export()` (static) |
| A category view | `$category` (the `Category` model), `$pages` (Collection of `Post` for the current page), `$paginator` (`Yazar\Support\Paginator`) | `ContentController::renderCategory()` (dynamic), `CategoryExporter::export()` (static) |
| The front page view (`config('yazar.front_page_view')`) | `$items` (Collection of `Post` for the current page), `$paginator` | `ContentController::renderMainPage()` (dynamic), `FrontPageExporter::export()` (static) |

On the document model itself (`$page`/`$category`), properties commonly used in views:

- `$page->meta` — the `DocumentMeta` object (`->title`, `->viewExtends`, `->createdAt`, plus any extra front matter field).
- `$page->html_content` — the Markdown body rendered to HTML. Use `{!! $page->html_content !!}`, unescaped — the content is trusted Markdown, not user input.
- `$page->url` — the resolved URL, without a leading slash (build links as `/{{ $page->url }}`, as the stub views do).
- `$page->published_at` — a `Carbon` instance built from `created_at`.

**Dynamic-only attributes.** `ContentController::renderDocument()` additionally sets three ad-hoc attributes on `$page` before rendering:

```php
$page->setAttribute('previousPage', $previousPage); // adjacent Document by published_at, or null
$page->setAttribute('nextPage', $nextPage);          // adjacent Document by published_at, or null
$page->setAttribute('category', $category);          // Category resolved from $page->meta->category, or null
```

**This does not happen during a static build.** `PageExporter`/`PostExporter::export()` call `view($viewName, ['page' => $post])` directly, without setting these three attributes — so `$page->previousPage`, `$page->nextPage`, and `$page->category` are always `null` on a statically built page. A view relying on them (prev/next post links, a category badge) renders correctly over HTTP but silently loses that data in `php artisan build` output. If you need this in static builds, resolve it in a custom `Exporter` instead — see [Content types](content-types.md).

The `<x-paginator>` component (`stubs/views/components/paginator.blade.php`) expects a single `paginator` prop and reads `$paginator->count`, `->currentPage`, `->prevLink`, `->nextLink`, `->window()`, `->url($page)` — see `Yazar\Support\Paginator` for the full API.

## See also

- [Urls](urls.md) — how the `slug`/`url` front matter fields and a model's `permalink()` combine into the final URL
- [Content types](content-types.md) — adding a new document type with its own view and exporter
- [Configuration](configuration.md) — `front_page_view`, `content_types`, and other options referenced above
