# Yazar

Yazar is a static site generator engine for Laravel: Markdown content with front matter is imported into Eloquent documents and rendered through Blade templates, either dynamically over HTTP or as pre-built static HTML.

For Laravel developers who want to:

- Use familiar tools: Eloquent, Blade, Service Container, Facades.
- Extend behaviour through config, not by forking the engine.
- Avoid duplicating Markdown-to-HTML logic across static and dynamic rendering.
- Get static-site speed without a separate templating language.

This repository is the engine only — a Composer package, not a runnable application. It is meant to be installed inside your own Laravel project.

## Installation

Until the package is tagged, install it as a local path dependency with a symlink, so changes to the engine are picked up immediately by the consuming application:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../yazar",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "seryak/yazar": "dev-master"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

```bash
composer require seryak/yazar:dev-master
```

Once versioned releases exist, this becomes a regular `composer require seryak/yazar`.

## Getting started

```bash
php artisan yazar:install   # publishes config/yazar.php, default views, and demo content
php artisan migrate         # creates the documents table
php artisan build           # generates the static site
```

`php artisan yazar:install` never touches the database — running migrations is always a separate, explicit step.

## Configuration

All engine configuration lives in the published `config/yazar.php`:

- `content_path` — root directory for Markdown content.
- `content_types` — maps a storage disk (`posts`/`pages`/`categories`) to a `DocumentType` and the Eloquent model class used for it; override the `model` value to use your own subclass.
- `disks` — the `Storage` disk definitions registered at runtime for each content type plus `static_output`.
- `deploy_target` — optional directory the `build` command copies its static output into.
- `front_page_view` — the Blade view name used for the front page.

Per-document view selection is not configured here: it comes from the `view::extends` front matter field on each Markdown file.

## Console commands

- `php artisan build` — imports all configured content, renders it to static HTML, and (if `deploy_target` is set) copies the result there.
- `php artisan yazar:install` — publishes config, default views, and demo content; supports `--force` to overwrite.

## Local development

The engine itself has no runnable Laravel application. `bin/harness-init.sh`
creates (or reuses) a git-ignored `harness/` Laravel application inside this
repository for manually exercising the package — see
[docs/harness.md](docs/harness.md) for setup, usage, and reset instructions.

---

## Documentation

| Guide | Description |
|-------|-------------|
| [Harness](docs/harness.md) | Local, git-ignored Laravel app for manually exercising the package |
