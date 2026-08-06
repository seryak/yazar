[← Конфигурация](configuration.md) · [Назад к README](../../README.ru.md) · [Harness →](harness.md)

# Добавление нового типа контента

## Обзор

Тип контента — это одна Eloquent-модель, наследующая абстрактный `Yazar\Models\Document`, реализующая `Yazar\Contracts\Documentable`, в паре с классом, реализующим `Yazar\Contracts\Exporter` для статической сборки, и зарегистрированная под своим ключом в `config('yazar.content_types')`. Все типы контента делят одну таблицу `documents` (см. [Конфигурация](configuration.md)) и один пайплайн импорта Markdown (`DocumentImportService`) — добавление типа не требует ни новой миграции, ни нового контроллера, ни нового кода импорта.

Готовые заготовки, с которых можно скопировать: `Post`, `Page`, `Category` в `src/Models/` и их экспортёры (`PostExporter`, `PageExporter`, `CategoryExporter`, а также `NullExporter` для типов, полностью отказывающихся от статического экспорта) в `src/Exporters/`.

## 1. Реализовать модель

```php
namespace App\Models; // где угодно в автозагрузке хост-приложения — тип контента не обязан жить в пакете

use Yazar\Exporters\NullExporter; // или собственный экспортёр, см. шаг 2
use Yazar\Models\Document;

class Note extends Document
{
    public static function documentType(): string
    {
        return 'note'; // сохраняется в documents.type; Document::booted() валидирует это при сохранении
    }

    public static function documentsPath(): string
    {
        return 'notes'; // подпапка на диске `content`: {content_path}/notes/*.md
    }

    public static function exporterClass(): string
    {
        return NullExporter::class;
    }

    public static function permalink(): string
    {
        return '/notes/:slug'; // см. URL — на данный момент реализован только :slug
    }
}
```

Все четыре статических метода приходят из `Yazar\Contracts\Documentable` (`src/Contracts/Documentable.php`) и обязательны — сам `Document` объявлен `abstract` и не может быть инстанцирован напрямую. `documentType()` проверяется при сохранении: `Document::booted()` выбрасывает `InvalidArgumentException`, если попытаться присвоить атрибут `type`, не совпадающий с ним, а global scope (`document_type`) прозрачно фильтрует любой запрос к модели строками этого типа — `Note::all()` никогда не увидит строку `Post`, хотя они лежат в одной таблице `documents`.

Если типу нужны собственные связи (как `Category::posts()`) — добавляйте их обычным образом Eloquent: помимо контракта `Documentable`, `Note` — самая обычная модель.

## 2. Реализовать (или переиспользовать) экспортёр

Каждый экспортёр реализует `Yazar\Contracts\Exporter::export(): void`, который `BuildCommand` вызывает как `new $exporterClass($modelClass)`. Три готовых формы для копирования:

- **Без статического вывода вообще** — переиспользуйте `Yazar\Exporters\NullExporter` для типа, который рендерится только динамически. Конструктор не объявлен; `BuildCommand` всегда передаёт `$modelClass` аргументом, но PHP молча отбрасывает его, если у класса нет `__construct()`. Ни один из трёх встроенных типов (`Post`, `Page`, `Category`) сейчас его не использует — это готовый вариант отказа от статического экспорта для тех типов, которым он не нужен.
- **Один файл на документ** — скопируйте `PostExporter`/`PageExporter`: перебор `$modelClass::all()`, рендер `$document->meta->viewExtends` с `['page' => $document]`, запись результата через `Storage::disk('static_output')->put($document->path_for_static_page, $html)`.
- **Постраничный листинг на документ** (как у `Category`) — скопируйте `CategoryExporter`: постройте `Yazar\Support\Paginator` и переберите `->pages()`, записывая `->path()` каждой страницы.

Переменные, которые ваш экспортёр передаёт в `view()`, должны совпадать с тем, что для той же вьюхи передаёт динамический маршрут — точный набор, который использует `ContentController`, см. в [Шаблонах](templates.md), включая оговорку про атрибуты (`previousPage`/`nextPage`/`category`), существующие только в динамическом рендеринге.

## 3. Зарегистрировать тип

```php
// config/yazar.php
'content_types' => [
    'posts' => Post::class,
    'pages' => Page::class,
    'categories' => Category::class,
    'notes' => Note::class, // новый тип
],
```

Ключ обходится обобщённо везде, где это важно (`DocumentImportService`/`ContentImporter`, `BuildCommand::exportContentType()`, `ContentController::show()`) — для совершенно нового типа подходит любое имя ключа. **Исключение:** `posts` и `categories` дополнительно читаются по фиксированному ключу в трёх местах внутри `ContentController` (`config('yazar.content_types.posts')` в `renderMainPage()`/`renderDocument()`, `config('yazar.content_types.categories')` в `showCategoryPage()`/`renderDocument()`). Добавление `notes` этого не затрагивает — но переименование самих `posts` или `categories` потребовало бы правки и этих мест.

## 4. Добавить контент и вьюху

Создайте `{content_path}/notes/` (совпадает с `documentsPath()`) и положите туда Markdown-файл:

```markdown
---
view::extends: note
title: First note
created_at: "2026-01-01"
---

Тело заметки здесь.
```

`view::extends: note` означает, что где-то среди путей вьюх хост-приложения должна существовать Blade-вьюха `note` — иначе `DocumentImportService` отклонит документ на импорте (проверка `view()->exists()` внутри `isValidOptions()`). Минимальная вьюха:

```blade
@extends('layout')

@section('main')
    <h1>{{ $page->meta->title }}</h1>
    {!! $page->html_content !!}
@endsection
```

## 5. Импортировать и проверить

```bash
php artisan migrate   # нужно один раз на проект — новая миграция для нового типа не требуется
php artisan build     # либо дождитесь автоимпорта через ImportEmptyContent при следующем запросе в динамическом режиме
```

Новая миграция не нужна: `notes` попадают в ту же таблицу `documents`, что и все остальные типы, различаясь только колонкой `type` и global scope, объявленным на `Note`.

## Смотри также

- [Шаблоны](templates.md) — поля front matter и переменные вьюх, на которые может рассчитывать новый тип
- [URL](urls.md) — как `permalink()` влияет на итоговый `url` и что происходит при коллизии
- [Конфигурация](configuration.md) — полная форма `content_types` и общего диска `content`
