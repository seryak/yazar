[← Configuration](configuration.md) · [Back to README](../../README.md)

# Harness: manual package testing

Yazar is a bare Composer package: the repository has no `app/`, `bootstrap/`, `public/`, or `artisan`, so it can't be run as an application on its own. **Harness** is a Laravel application in the `harness/` subfolder that makes this possible locally, without turning the package repository back into a Laravel skeleton.

## What it is and why

- `harness/` is created and reused by the `bin/harness-init.sh` script — it is not stored in git (`.gitignore` contains `/harness`).
- It requires `seryak/yazar` through a Composer path-repository with `symlink: true` pointing at the repository root (`../` relative to `harness/`) — changes to the package's `src/` are visible in harness instantly, without reinstalling the dependency.
- Reusable, not one-shot: `bin/harness-init.sh` can be run repeatedly — if `harness/` already exists, it doesn't recreate the Laravel application from scratch, it only updates the `seryak/yazar` dependency inside it.

This is the same mechanism (path-repository + `symlink: true`) used to install the package in the README for a [real third-party consumer](../../README.md#installation) — the only difference is that harness lives inside the package repository itself and is meant for the developer, not the end user.

## Prerequisites

- PHP 8.2+ and Composer 2.x (same as the package itself requires).
- Network access for `composer create-project laravel/laravel` on first run.

## Quick start

```bash
bin/harness-init.sh
cd harness
php artisan yazar:install
php artisan migrate
php artisan build
php artisan serve
```

- `bin/harness-init.sh` — creates `harness/` (if it doesn't exist yet) or updates `seryak/yazar` in it (if it already exists).
- `php artisan yazar:install` — publishes `config/yazar.php`, default views, and demo content; never touches the database.
- `php artisan migrate` — creates the `documents` table.
- `php artisan build` — imports content and renders the static site.
- `php artisan serve` — starts harness as a regular Laravel application for viewing in a browser.

## What `bin/harness-init.sh` does

```bash
if [ ! -d harness ]; then
    composer create-project laravel/laravel harness "^12.0"
    composer config --working-dir=harness repositories.yazar '{"type":"path","url":"../","options":{"symlink":true}}' --json
    composer require --working-dir=harness seryak/yazar:dev-master
else
    composer update --working-dir=harness seryak/yazar
fi
```

- **First run** (`harness/` absent): creates a Laravel application via `composer create-project laravel/laravel harness "^12.0"`, sets up a path-repository with `symlink: true` in a single `composer config` JSON call, then requires `seryak/yazar:dev-master`.
- **Subsequent runs** (`harness/` already exists): doesn't recreate the application — only updates the `seryak/yazar` dependency, leaving the rest of harness (database, published files, `.env`) as is.

> **The `"^12.0"` version constraint is required.** It matches the major `illuminate/*` version the package's `composer.json` requires. Without it, `composer create-project` installs the latest Laravel version overall (13.x at the time of writing), and `composer require seryak/yazar:dev-master` in such a harness fails with a resolve conflict (`illuminate/console ^12.0` vs. the already installed `laravel/framework ^13.x`). If the package ever moves to Laravel 13, this version needs to be bumped in the script along with `composer.json`.

## Resetting harness

`harness/` is safe to delete at any time — it isn't in git and contains nothing the package repository needs:

```bash
rm -rf harness
bin/harness-init.sh
```

Useful if harness ends up in an inconsistent state (e.g. its `composer.json` was manually corrupted, or it was created before the Laravel version fix and contains an incompatible `laravel/framework`) — the script doesn't repair an existing configuration, it only reuses it or creates it from scratch.

## Limitations

- Harness is meant only for **manual** testing of the package (`yazar:install`, `migrate`, `build`, `serve`) — it does not run the package's existing PHPUnit test suite (`tests/`). The tests physically live in the repository but aren't run here: they depend on `bootstrap/app.php`, which no longer exists in the bare package, and wiring them up through harness was a deliberate non-goal — it would tie the tests to a specific local path and break them in CI.
- Harness is not a prototype or a draft of a future personal site built on the Yazar engine; it is strictly a developer tool for testing the package itself.

## See also

- [Installing the package in a third-party application](../../README.md#installation) — the same path-repository mechanism, but for a real package consumer
- [Console commands](../../README.md#console-commands) — the `build` and `yazar:install` commands used inside harness
- [Configuration](configuration.md) — what `yazar:install` publishes and what each `config/yazar.php` option controls
