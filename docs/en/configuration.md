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
    'posts' => ['type' => 'post', 'model' => Post::class],
    'pages' => ['type' => 'page', 'model' => Page::class],
    'categories' => ['type' => 'category', 'model' => Category::class],
],
```

Each entry describes one content type:

- **Key** (`posts`/`pages`/`categories`) — used by `DocumentImportService::importAllConfiguredDisks()` as the name of the disk files are imported from; must match the key of the same name in `disks` (see below).
- **`type`** — the value stored in the `type` column of the `documents` table. Must match what `documentType()` returns on the given model — `Document` rejects creating/updating a record if it doesn't match (the `Yazar\Contracts\Documentable` contract).
- **`model`** — the Eloquent model class for this type. Must implement `Yazar\Contracts\Documentable`; can be overridden with your own subclass (`Post`, `Page`, `Category`, or a descendant) without touching package code.

**Important nuance:** the `posts` and `categories` keys are additionally used directly, by a fixed name, in `ContentController` and `BuildCommand` (`config('yazar.content_types.posts.model')`, `config('yazar.content_types.categories.model')`) — these are not fully arbitrary names. A new content type can be added under any key, but renaming `posts`/`categories` in the config requires updating those two classes.

## `disks`

```php
'disks' => [
    'posts' => ['driver' => 'local', 'root' => $contentPath.'/posts', 'throw' => false],
    'pages' => ['driver' => 'local', 'root' => $contentPath.'/pages', 'throw' => false],
    'categories' => ['driver' => 'local', 'root' => $contentPath.'/categories', 'throw' => false],
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

- **`posts`/`pages`/`categories`** — each disk points to its own subdirectory inside `content_path`. Keys here **must** match `content_types` keys — `DocumentImportService` resolves the disk by the same name. `throw: false` — a missing directory on the disk does not throw an exception during import.
- **`static_output`** — the disk that `php artisan build` writes finished HTML pages to. `root` is the same directory set by the `output_directory` option (see above), but read via a separate `env('OUTPUT_DIRECTORY', 'build')` call. `url`/`visibility: public` are needed if this disk's contents are meant to be served directly as public files.

## `markdown`

```php
'markdown' => [
    'extensions' => [
        // \Yazar\Markdown\Extensions\PhikiHighlightExtension::class,
    ],
    'phiki' => [
        'theme' => env('YAZAR_CODE_THEME', 'github-light'),
        'default_grammar' => env('YAZAR_CODE_DEFAULT_GRAMMAR', 'shellscript'),
    ],
],
```

- **`extensions`** — a list of `League\CommonMark\Extension\ExtensionInterface` class-strings that `Yazar\Markdown\MarkdownParser::__construct()` adds to the `Environment` on top of the required core (`CommonMarkCoreExtension` + `FrontMatterExtension`). Empty by default — without explicitly adding a class to this list, markdown rendering behavior does not change.
- **`phiki`** — settings for `Yazar\Markdown\Extensions\PhikiHighlightExtension` (syntax highlighting for fenced code blocks via the `phiki/phiki` library). The extension reads these values itself; they have no effect until `PhikiHighlightExtension::class` is added to `extensions` above.
  - **`theme`** — the display theme, passed to `Phiki::codeToHtml()` (valid values are `Phiki\Theme\Theme` slugs, e.g. `'github-light'`, `'dracula'`, `'nord'`).
  - **`default_grammar`** — grammar applied to fenced blocks without a specified language (a bare ` ``` ` with no word after the backticks). The slug must match one of the `Phiki\Grammar\Grammar` cases (e.g. `'shellscript'`, `'php'`, `'json'`).

**How to enable highlighting:** add `\Yazar\Markdown\Extensions\PhikiHighlightExtension::class` to `markdown.extensions` in the host application's published `config/yazar.php`. The block's language is taken from the fence's info string (` ```php ` → `php`); blocks without a language use `default_grammar`.

## See also

- [Harness](harness.md) — how to test configuration locally against a real Laravel application
- [Console commands](../../README.md#console-commands) — the `build` and `yazar:install` commands that read these options
