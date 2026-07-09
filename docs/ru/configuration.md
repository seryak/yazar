[Назад к README](../../README.ru.md) · [Harness →](harness.md)

# Конфигурация

После `php artisan yazar:install` весь конфиг движка публикуется в `config/yazar.php`. Ниже — за что отвечает каждая опция, со ссылками на код, который её читает.

## Скалярные опции

| Опция | ENV-переменная | По умолчанию | За что отвечает |
|-------|-----------------|----------------|------------------|
| `content_path` | `YAZAR_CONTENT_PATH` | `base_path('_content')` | Корневая директория Markdown-контента. Используется хелпером `content_path()` — из неё строится полный путь до файла (`content_path('posts/hello.md')`). |
| `deploy_target` | `YAZAR_DEPLOY_TARGET` | `null` | Директория, куда `php artisan build` копирует готовый статический сайт (`BuildCommand::move()`). Если не задана — `build` только генерирует файлы в `public/{output_directory}`, копирование во внешнюю директорию пропускается. |
| `front_page_view` | `YAZAR_FRONT_PAGE_VIEW` | `'front-page'` | Имя Blade-вьюхи для главной страницы (ленты последних постов). Читается и в динамическом рендеринге (`ContentController`), и при статической сборке (`BuildCommand`). |
| `render_mode` | `CONTENT_RENDER_MODE` | `'dynamic'` | Зарезервирована для переключения между динамическим рендерингом и статической сборкой. **На данный момент нигде в коде не читается** — оба режима и так работают через независимые друг от друга механизмы (`ContentController` для HTTP, `php artisan build` для статики), значение этой опции ни на что не влияет. |
| `pagination_per_page` | `CONTENT_PAGINATION_PER_PAGE` | `1` | Количество документов на одной странице пагинации — и на главной странице, и в категориях. Приводится к `int` и не может быть меньше `1` (`max((int) config(...), 1)` в `ContentController`/`BuildCommand`). |
| `use_html_suffix` | `USE_HTML_SUFFIX` | `false` | Формат путей статических страниц (`Document::getPathForStaticPageAttribute()`). `false` → `slug/index.html` (работает без серверных правил переписывания URL); `true` → `slug.html`. |
| `output_directory` | `OUTPUT_DIRECTORY` | `'build'` | Имя поддиректории внутри `public/`, куда пишет диск `static_output` при `php artisan build`. Значение используется дважды — как отдельная опция и как `root` диска `static_output` в массиве `disks` (см. ниже) — оба места читают одну и ту же ENV-переменную независимо друг от друга. |
| `storage_url` | `STORAGE_URL` | `''` | Базовый URL, который хелпер `storage()` подставляет перед относительным путём к файлу: `storage($path)` → `config('yazar.storage_url').$path`. Нужен, если статические ассеты отдаются не с того же домена, что сам сайт. |

## `content_types`

```php
'content_types' => [
    'posts' => ['type' => 'post', 'model' => Post::class],
    'pages' => ['type' => 'page', 'model' => Page::class],
    'categories' => ['type' => 'category', 'model' => Category::class],
],
```

Каждая запись описывает один тип контента:

- **Ключ** (`posts`/`pages`/`categories`) — используется `DocumentImportService::importAllConfiguredDisks()` как имя диска, с которого импортируются файлы; должен совпадать с ключом того же имени в `disks` (см. ниже).
- **`type`** — значение, которое попадает в колонку `type` таблицы `documents`. Должно совпадать с тем, что возвращает `documentType()` указанной модели — `Document` отклоняет создание/обновление записи, если это не так (контракт `Yazar\Contracts\Documentable`).
- **`model`** — класс Eloquent-модели для этого типа. Обязан реализовывать `Yazar\Contracts\Documentable`; можно переопределить на собственный подкласс (`Post`, `Page`, `Category` или их наследник) без изменения кода пакета.

**Важный нюанс:** ключи `posts` и `categories` дополнительно используются напрямую, по фиксированному имени, в `ContentController` и `BuildCommand` (`config('yazar.content_types.posts.model')`, `config('yazar.content_types.categories.model')`) — это не полностью произвольные имена. Новый тип контента можно добавить под любым ключом, но переименовывать `posts`/`categories` в конфиге нельзя без правки этих двух классов.

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

Определения Laravel `Storage`-дисков, которые `YazarServiceProvider::boot()` регистрирует во время выполнения (`config(["filesystems.disks.$name" => $diskConfig])`) — они не описываются в стандартном `config/filesystems.php` приложения.

- **`posts`/`pages`/`categories`** — каждый диск указывает на свою поддиректорию внутри `content_path`. Ключи здесь **должны** совпадать с ключами `content_types` — `DocumentImportService` резолвит диск по тому же имени. `throw: false` — отсутствие директории на диске не бросает исключение при импорте.
- **`static_output`** — диск, куда `php artisan build` записывает готовые HTML-страницы. `root` — та же директория, что задаётся опцией `output_directory` (см. выше), но читается отдельным вызовом `env('OUTPUT_DIRECTORY', 'build')`. `url`/`visibility: public` нужны, если содержимое этого диска предполагается отдавать напрямую как публичные файлы.

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

- **`extensions`** — список class-string'ов `League\CommonMark\Extension\ExtensionInterface`, которые `Yazar\Markdown\MarkdownParser::__construct()` добавляет в `Environment` поверх обязательного ядра (`CommonMarkCoreExtension` + `FrontMatterExtension`). По умолчанию пуст — без явного добавления класса в этот список поведение рендеринга markdown не меняется.
- **`phiki`** — настройки для `Yazar\Markdown\Extensions\PhikiHighlightExtension` (подсветка синтаксиса fenced-код-блоков через библиотеку `phiki/phiki`). Расширение читает эти значения самостоятельно, они не влияют ни на что, пока `PhikiHighlightExtension::class` не добавлен в `extensions` выше.
  - **`theme`** — тема оформления, передаётся в `Phiki::codeToHtml()` (валидные значения — слаги `Phiki\Theme\Theme`, например `'github-light'`, `'dracula'`, `'nord'`).
  - **`default_grammar`** — грамматика, применяемая к fenced-блокам без указанного языка (голый ` ``` ` без слова после бэктиков). Слаг должен совпадать с одним из кейсов `Phiki\Grammar\Grammar` (например, `'shellscript'`, `'php'`, `'json'`).

**Как включить подсветку:** добавить `\Yazar\Markdown\Extensions\PhikiHighlightExtension::class` в `markdown.extensions` в опубликованном `config/yazar.php` хост-приложения. Язык блока берётся из info-string фенса (` ```php ` → `php`); для блоков без языка используется `default_grammar`.

## Смотри также

- [Harness](harness.md) — как локально проверить конфигурацию на реальном Laravel-приложении
- [Консольные команды](../../README.ru.md#консольные-команды) — команды `build` и `yazar:install`, которые читают эти опции
