[Back to README](../../README.md) · [Harness →](harness.md)

# Configuration

After `php artisan yazar:install`, all engine configuration is published to `config/yazar.php`. Below is what each option controls, with references to the code that reads it.

## Scalar options

| Option | ENV variable | Default | What it controls |
|-------|-----------------|----------------|------------------|
| `content_path` | `YAZAR_CONTENT_PATH` | `base_path('_content')` | Root directory for Markdown content. Used by the `content_path()` helper — it builds the full path to a file (`content_path('posts/hello.md')`). |
| `deploy_target` | `YAZAR_DEPLOY_TARGET` | `null` | Directory that `php artisan build` copies the finished static site to (`BuildCommand::move()`). If unset — `build` only generates files into `public/{output_directory}`, copying to an external directory is skipped. |
| `front_page_view` | `YAZAR_FRONT_PAGE_VIEW` | `'front-page'` | Blade view name for the front page (feed of latest posts). Read both in dynamic rendering (`ContentController`) and during static build (`BuildCommand`). |
| `render_mode` | `CONTENT_RENDER_MODE` | `'dynamic'` | Reserved for switching between dynamic rendering and static build. **Not currently read anywhere in the code** — both modes already work through mechanisms independent of each other (`ContentController` for HTTP, `php artisan build` for static), so this option's value has no effect. |
| `pagination_per_page` | `CONTENT_PAGINATION_PER_PAGE` | `1` | Number of documents per pagination page — both on the front page and in categories. Cast to `int` and cannot be less than `1` (`max((int) config(...), 1)` in `ContentController`/`BuildCommand`). |
| `use_html_suffix` | `USE_HTML_SUFFIX` | `false` | Static page path format (`Document::getPathForStaticPageAttribute()`). `false` → `slug/index.html` (works without server URL-rewrite rules); `true` → `slug.html`. |
| `output_directory` | `OUTPUT_DIRECTORY` | `'build'` | Name of the subdirectory inside `public/` that the `static_output` disk writes to during `php artisan build`. The value is used twice — as a standalone option and as the `root` of the `static_output` disk in the `disks` array (see below) — both places read the same ENV variable independently. |
| `storage_url` | `STORAGE_URL` | `''` | Base URL that the `storage()` helper prepends to a relative file path: `storage($path)` → `config('yazar.storage_url').$path`. Needed if static assets are served from a different domain than the site itself. |

## `content_types`

```php
'content_types' => [
    'posts' => Post::class,
    'pages' => Page::class,
    'categories' => Category::class,
],
```

A flat map from a key to an Eloquent model class implementing `Yazar\Contracts\Documentable`:

- **Key** (`posts`/`pages`/`categories`) — iterated generically by `DocumentImportService::importAllConfiguredModels()`, `BuildCommand`, and `ContentController::show()`. The `posts` and `categories` keys are additionally read directly, by fixed name, in three places in `ContentController` (`showCategoryPage()`, `renderMainPage()`, `renderDocument()` — e.g. `config('yazar.content_types.posts')`). A new content type can be added under any key, but renaming `posts`/`categories` requires updating those call sites.
- **Value** — the model class itself. Must implement `Yazar\Contracts\Documentable`: `documentType(): string` (the value stored in the `documents.type` column — `Document` rejects creating/updating a record if it doesn't match), `documentsPath(): string` (the model's subfolder on the shared `content` disk, see `disks` below), and `exporterClass(): class-string<Exporter>` (the static-export logic `php artisan build` uses for this type). Can be overridden with your own subclass (`Post`, `Page`, `Category`, or a descendant) without touching package code.

## `disks`

```php
'disks' => [
    'content' => ['driver' => 'local', 'root' => $contentPath, 'throw' => false],
    'static_output' => [
        'driver' => 'local',
        'root' => public_path(env('OUTPUT_DIRECTORY', 'build')),
        'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
        'visibility' => 'public',
        'throw' => false,
        'report' => false,
    ],
],
```

Definitions of Laravel `Storage` disks that `YazarServiceProvider::boot()` registers at runtime (`config(["filesystems.disks.$name" => $diskConfig])`) — they are not described in the host application's standard `config/filesystems.php`.

- **`content`** — a single shared disk for all Markdown content, rooted at `content_path`. Each `Documentable` model declares its own subfolder within it via `documentsPath()` (e.g. `Post::documentsPath()` returns `'posts'`); `DocumentImportService` lists `Storage::disk('content')->allFiles($modelClass::documentsPath())` and strips the subfolder prefix itself before storing `path`/`slug` (so URLs stay `/hello-world/`, not `/posts/hello-world/`). `throw: false` — a missing directory does not throw an exception during import.
- **`static_output`** — the disk that `php artisan build` writes finished HTML pages to. `root` is the same directory set by the `output_directory` option (see above), but read via a separate `env('OUTPUT_DIRECTORY', 'build')` call. `url`/`visibility: public` are needed if this disk's contents are meant to be served directly as public files.

## `markdown`

```php
'markdown' => [
    'extensions' => [
        // \Yazar\Markdown\Extensions\PhikiHighlightExtension::class,
        // \Yazar\Markdown\Extensions\DiskUrlExtension::class,
    ],
    'default_disk' => env('YAZAR_MARKDOWN_DEFAULT_DISK'),
    'phiki' => [
        'theme' => env('YAZAR_CODE_THEME', 'github-light'),
        'default_grammar' => env('YAZAR_CODE_DEFAULT_GRAMMAR', 'shellscript'),
    ],
],
```

- **`extensions`** — a list of `League\CommonMark\Extension\ExtensionInterface` class-strings that `Yazar\Markdown\MarkdownParser::__construct()` adds to the `Environment` on top of the required core (`CommonMarkCoreExtension` + `FrontMatterExtension`). Empty by default — without explicitly adding a class to this list, markdown rendering behavior does not change.
- **`default_disk`** — the disk used by `Yazar\Markdown\Extensions\DiskUrlExtension` when a `disk://path` reference omits the disk name (see below). `null` by default — falls back to the host application's own default filesystem disk (`config('filesystems.default')`).
- **`phiki`** — settings for `Yazar\Markdown\Extensions\PhikiHighlightExtension` (syntax highlighting for fenced code blocks via the `phiki/phiki` library). The extension reads these values itself; they have no effect until `PhikiHighlightExtension::class` is added to `extensions` above.
  - **`theme`** — the display theme, passed to `Phiki::codeToHtml()` (valid values are `Phiki\Theme\Theme` slugs, e.g. `'github-light'`, `'dracula'`, `'nord'`).
  - **`default_grammar`** — grammar applied to fenced blocks without a specified language (a bare ` ``` ` with no word after the backticks). The slug must match one of the `Phiki\Grammar\Grammar` cases (e.g. `'shellscript'`, `'php'`, `'json'`).

**How to enable highlighting:** add `\Yazar\Markdown\Extensions\PhikiHighlightExtension::class` to `markdown.extensions` in the host application's published `config/yazar.php`. The block's language is taken from the fence's info string (` ```php ` → `php`); blocks without a language use `default_grammar`.

## Linking to files on a Laravel disk from Markdown

`Yazar\Markdown\Extensions\DiskUrlExtension` lets Markdown content reference a file on *any* registered Laravel disk — not just the engine's own `content`/`static_output` disks, but any key under `filesystems.disks`, including your host application's `public`, `s3`, or a custom disk — without hardcoding an absolute URL that would differ between environments.

Write `disk(diskName)://path/to/file.ext` anywhere in the document, and it resolves to `Storage::disk('diskName')->url('path/to/file.ext')` at render time:

```markdown
![Screenshot](disk(s3)://screenshots/dashboard.png)

See the [full report](disk(media)://reports/2026-q1.pdf) for details.

You can also drop disk(s3)://screenshots/dashboard.png directly into a sentence,
or inside raw HTML: <img src="disk(s3)://screenshots/dashboard.png">.
```

The disk name can be omitted — `disk://path/to/file.ext` resolves against `markdown.default_disk` (see the `markdown` section above) instead:

```markdown
![Screenshot](disk://screenshots/dashboard.png)
```

- Works inside `![alt](...)`, `[text](...)`, plain paragraph text, and raw HTML — not just inside link/image syntax.
- Left untouched inside fenced code blocks and inline code, so you can show the syntax as a literal example without it being resolved.
- Front matter fields (the YAML block at the top of the file) are **not** processed by this extension — only the Markdown body.
- No disk allow-list: if the disk isn't registered, or its driver can't produce a URL, resolving throws `Yazar\Markdown\Extensions\DiskUrlResolutionException` (wrapping the original error) instead of silently falling back to the raw text. During import (`DocumentImportService`, used by `php artisan build`), a document with a broken disk reference is marked invalid instead of failing the whole import batch.

**How to enable:** add `\Yazar\Markdown\Extensions\DiskUrlExtension::class` to `markdown.extensions` in the host application's published `config/yazar.php`.

## See also

- [Harness](harness.md) — how to test configuration locally against a real Laravel application
- [Console commands](../../README.md#console-commands) — the `build` and `yazar:install` commands that read these options
