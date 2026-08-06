[Назад к README](../../README.ru.md) · [URL →](urls.md)

# Шаблоны и front matter

У каждого Markdown-файла две части: блок front matter в YAML (ограничен строками `---` в начале файла, парсится через `FrontMatterParserInterface` из League CommonMark) и Markdown-тело под ним. Front matter превращается в `$document->meta` — объект `Yazar\ValueObjects\DocumentMeta`; тело — в `$document->content`/`$document->html_content`.

## Поля front matter

**Обязательные** — проверяются `DocumentImportService::isValidOptions()` перед импортом; файл без одного из них или с невалидным значением пропускается и попадает в `invalid_documents` вместо импорта:

| Поле | Тип | Назначение |
|------|-----|------------|
| `view::extends` | строка | Имя Blade-вьюхи, которая рендерит этот документ. Должна существовать (`view()->exists()`), иначе документ отклоняется на импорте. |
| `title` | строка | Заголовок документа, доступен через `$meta->title`. |
| `created_at` | строка, парсится как дата | Сохраняется в `published_at` (`Carbon::parse()`), определяет порядок на главной странице и в категориях. |

Обратите внимание на `::` в `view::extends` — это буквальная часть ключа front matter, а не специфичный для Yazar YAML-синтаксис. Просто такой ключ — валидный YAML mapping key.

**Распознаваемые опциональные поля:**

| Поле | Тип | Назначение |
|------|-----|------------|
| `slug` | строка | Подставляется в токен `:slug` при вычислении `url` документа. Если не задан — берётся имя файла (без `.md`) — см. [URL](urls.md). |
| `url` | строка | Полностью переопределяет вычисленный `url`, минуя шаблон `permalink()` модели — см. [URL](urls.md). |
| `category` | строка | `slug` документа `Category`. Имеет смысл только для `Post`: `Category::posts()` — это `hasMany` по ключу `meta->category = slug`. На импорте не проверяется на существование такой категории — опечатка молча даёт отсутствие совпадения. |

**Всё остальное**, что вы положите в front matter (например, `description` и `cover_image` в demo-контенте темы-заглушки), сохраняется как есть и доступно через `$meta->yourField`. `DocumentMeta` хранит все нераспознанные ключи во внутреннем массиве `extra` и возвращает их через `__get()`/`get()` (`src/ValueObjects/DocumentMeta.php`). Кроме трёх обязательных полей выше жёсткой схемы нет — новое метаполе не требует изменений в коде, только новый ключ в front matter и соответствующий `{{ $page->meta->key }}` во вьюхе.

## Переменные Blade-шаблонов

Набор переменных, которые получает вьюха, зависит от того, какая страница рендерится, и одинаков в динамическом (`ContentController`) и статическом (`Exporters\*`) рендеринге — за одним исключением, о нём ниже.

| Рендеринг | Передаваемые переменные | Устанавливает |
|-----------|--------------------------|----------------|
| Вьюха страницы/поста (та, что указана в `view::extends`) | `$page` — сама модель `Page`/`Post` | `ContentController::renderDocument()` (динамика), `PageExporter`/`PostExporter::export()` (статика) |
| Вьюха категории | `$category` (модель `Category`), `$pages` (Collection постов `Post` на текущей странице), `$paginator` (`Yazar\Support\Paginator`) | `ContentController::renderCategory()` (динамика), `CategoryExporter::export()` (статика) |
| Вьюха главной страницы (`config('yazar.front_page_view')`) | `$items` (Collection постов `Post` на текущей странице), `$paginator` | `ContentController::renderMainPage()` (динамика), `FrontPageExporter::export()` (статика) |

На самой модели документа (`$page`/`$category`) — свойства, которыми чаще всего пользуются вьюхи:

- `$page->meta` — объект `DocumentMeta` (`->title`, `->viewExtends`, `->createdAt`, плюс любое дополнительное поле front matter).
- `$page->html_content` — Markdown-тело, отрендеренное в HTML. Используйте `{!! $page->html_content !!}`, без экранирования — контент считается доверенным Markdown, а не пользовательским вводом.
- `$page->url` — вычисленный URL без ведущего слеша (ссылки строятся как `/{{ $page->url }}`, как это делают вьюхи-заглушки).
- `$page->published_at` — экземпляр `Carbon`, построенный из `created_at`.

**Атрибуты, доступные только в динамике.** `ContentController::renderDocument()` дополнительно проставляет на `$page` три ad-hoc атрибута перед рендером:

```php
$page->setAttribute('previousPage', $previousPage); // соседний Document по published_at, либо null
$page->setAttribute('nextPage', $nextPage);          // соседний Document по published_at, либо null
$page->setAttribute('category', $category);          // Category, найденная по $page->meta->category, либо null
```

**При статической сборке этого не происходит.** `PageExporter`/`PostExporter::export()` вызывают `view($viewName, ['page' => $post])` напрямую, без установки этих трёх атрибутов — поэтому `$page->previousPage`, `$page->nextPage` и `$page->category` на статически собранной странице всегда `null`. Вьюха, завязанная на них (ссылки «следующий/предыдущий пост», плашка категории), корректно работает по HTTP, но молча теряет эти данные в выводе `php artisan build`. Если это нужно и в статической сборке — вычисляйте их в собственном `Exporter` — см. [Типы контента](content-types.md).

Компонент `<x-paginator>` (`stubs/views/components/paginator.blade.php`) ожидает единственный проп `paginator` и читает `$paginator->count`, `->currentPage`, `->prevLink`, `->nextLink`, `->window()`, `->url($page)` — полный API см. в `Yazar\Support\Paginator`.

## Смотри также

- [URL](urls.md) — как поля front matter `slug`/`url` и `permalink()` модели складываются в итоговый URL
- [Типы контента](content-types.md) — добавление нового типа документа со своей вьюхой и экспортёром
- [Конфигурация](configuration.md) — `front_page_view`, `content_types` и другие опции, упомянутые выше
