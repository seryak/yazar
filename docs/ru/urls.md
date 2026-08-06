[← Шаблоны](templates.md) · [Назад к README](../../README.ru.md) · [Конфигурация →](configuration.md)

# URL и permalink

## Как вычисляется `url` документа

Колонка `url` вычисляется один раз, на импорте, внутри `DocumentImportService::persist()` — никогда во время запроса или рендера. Порядок приоритета:

1. **`url` из front matter** (непустая строка) — используется как есть. Шаблон `permalink()` модели вообще не применяется.
2. Иначе — **шаблон `permalink()` модели**, с токеном `:slug`, подставленным значением `slug` из front matter (непустая строка), если оно задано.
3. Иначе — **шаблон `permalink()` модели**, с `:slug`, подставленным именем файла (относительно `documentsPath()` модели, без `.md`).

Результат сохраняется с обрезанными ведущим и конечным `/` (`trim($url, '/')`).

| Модель | `permalink()` | Файл | Front matter | Итоговый `url` |
|--------|----------------|------|---------------|------------------|
| `Page` | `/:slug` | `pages/about.md` | нет | `about` |
| `Post` | `/blog/:slug` | `posts/2019-old-post.md` | `slug: old-post` | `blog/old-post` |
| `Post` | `/blog/:slug` | любой | `url: archive/legacy` | `archive/legacy` — без префикса `blog/`, `url` из front matter полностью минует шаблон |
| `Category` | `/:slug` | `categories/example.md` | нет | `example` |

`permalink()` объявляется на каждой модели (`Yazar\Contracts\Documentable::permalink()`) как строка с ведущим слешем и плейсхолдерами `:token`. **На данный момент реализован только `:slug`.** Сам резолвер универсален — `Yazar\Documents\PermalinkResolver::resolve()` подставляет каждый `:key`, присутствующий в переданном ему массиве `$tokens`:

```php
// src/Documents/PermalinkResolver.php
PermalinkResolver::resolve('/blog/:slug', ['slug' => 'hello-world']); // => 'blog/hello-world'
```

Чтобы добавить второй токен (например, `:category`), нужно изменить вызывающий код в `DocumentImportService::persist()` — единственном месте, которое строит массив `$tokens` — а не сам резолвер, который уже умеет работать с произвольной картой токенов.

## `url` — единственный источник истины для обоих режимов рендеринга

`documents.url` — единственное, что читают и `ContentController` (динамический HTTP-роутинг — обычный поиск `WHERE url = ?`), и `Document::getPathForStaticPageAttribute()` (путь статической сборки), чтобы разместить документ. Отдельного вычисления URL для статики и динамики нет. Пост, доступный по HTTP на `/blog/hello-world`, всегда собирается в `blog/hello-world/index.html` (либо `blog/hello-world.html` при `use_html_suffix` — см. [Конфигурация](configuration.md)) на диске `static_output`.

Маршрутизация определена в `routes/web.php`:

```php
Route::get('/', [ContentController::class, 'renderMainPage'])->name('front-page');
Route::get('/{pageNumber}', [ContentController::class, 'renderMainPage'])->whereNumber('pageNumber');
Route::get('/{url}/{pageNumber}', [ContentController::class, 'showCategoryPage'])->where('url', '.+')->whereNumber('pageNumber');
Route::get('/{url}', [ContentController::class, 'show'])->where('url', '.+');
```

- `{url}` соответствует `.+`, поэтому многосегментные URL (например, `tools/jetbrains/test2`) работают — весь путь после домена ищется как единая строка по `documents.url`, а не разрешается по сегментам.
- Чисто числовой путь верхнего уровня (`/2`) совпадает с маршрутом пагинации главной страницы (`{pageNumber}`) *раньше*, чем с catch-all маршрутом `{url}`, поскольку Laravel сопоставляет маршруты в порядке их объявления. Документ, чей `url` оказался числом (например, страница с `slug: 2`), недоступен по `/2` — его перекрывает пагинация главной страницы.
- `ContentController::show()` перебирает `config('yazar.content_types')` по порядку и возвращает первую модель, в таблице которой нашёлся совпадающий `url`. Поскольку `url` уникален среди всех типов (см. ниже), совпасть может максимум один.

## Коллизии

Перед сохранением `DocumentImportService::persist()` проверяет, не занят ли вычисленный `url` *другим* документом — любого типа, не только текущего (проверка идёт прямым запросом к таблице `documents`, в обход global scope каждой модели, ограничивающего её своим типом):

- **Первый** обработанный документ, претендующий на `url`, сохраняет его за собой.
- Каждый следующий документ, вычисляющий тот же `url`, **пропускается** — импорт остальной части набора продолжается, а не прерывается.
- Пропущенные пути собираются в `url_conflicts` (`path => url`), отдельно от `invalid_documents` (зарезервированного за ошибками front matter/парсинга) — коллизия URL — не ошибка валидации самого документа.
- Уникальный констрейнт на `documents.url` на уровне БД — вторая линия защиты для гонок, которые пропустила предварительная проверка; итоговый `QueryException` перехватывается и попадает в тот же `url_conflicts`, а не всплывает необработанным исключением.

**Отчёт о коллизиях выводится только при `php artisan build`.** `BuildCommand::handle()` печатает его после импорта:

```
2 документов не получили уникальный url при импорте:
  - posts/duplicate.md: url 'blog/hello-world' уже занят другим документом
  - pages/dup2.md: url 'about' уже занят другим документом
```

Этот текст захардкожен на русском в `BuildCommand::handle()` независимо от локали хост-приложения или настроек `language.*` в `.kodla/config.yaml` — под окружение он не переводится. Сборка всё равно завершается с кодом `0` — коллизия — предупреждение, а не сбой сборки.

Та же коллизия, произошедшая при автоимпорте в динамическом режиме (middleware `ImportEmptyContent` → `ContentImporter::importIfEmpty()`), **вообще не даёт отчёта** — `importIfEmpty()` отбрасывает свои конфликты, поскольку у веб-запроса нет консоли для вывода. Проигравший документ просто не резолвится ни по какому URL, пока коллизию не устранят и контент не переимпортируют.

## Смотри также

- [Шаблоны](templates.md) — поля front matter `slug`, `url` и `category`, упомянутые выше
- [Типы контента](content-types.md) — реализация `permalink()` на новой модели
- [Конфигурация](configuration.md) — `use_html_suffix` и карта `content_types`
