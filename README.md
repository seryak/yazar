# Yazar is a static site generator on Laravel steroids.

Yazar is a static site generator that uses the familiar Laravel stack. Write templates in the powerful Blade, extend functionality through service containers and providers, as in a regular Laravel application, but output fast static HTML files.

For Laravel developers who want to:

- Use familiar tools: Blade, Service Container, Facades (if you want).
- Easily extend: Add custom logic through providers, as in any Laravel package.
- Avoid code duplication: Move common components and logic from your web applications to static sites.
- Get the speed of static sites without needing to learn a new templating framework (Jinja2, Liquid, etc.).

## Console commands

Project includes custom Artisan commands in [`app/Console/Commands`](app/Console/Commands):

- [`build`](app/Console/Commands/Build.php:17) — generates static pages and categories, builds front page pagination, then copies generated assets.
  Usage:

  ```bash
  php artisan build
  ```

  Notes:
  - command entrypoint: [`Build::handle()`](app/Console/Commands/Build.php:24)
  - final copy step uses hardcoded target paths in [`Build::move()`](app/Console/Commands/Build.php:78)

- [`test`](app/Console/Commands/Test.php:18) — debug command, currently dumps all pages from DB and stops execution.
  Usage:

  ```bash
  php artisan test
  ```

  Note:
  - current behavior is defined by [`dd(PageEloquent::all())`](app/Console/Commands/Test.php:24), so this command is intended for local diagnostics.
