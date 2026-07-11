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
    'posts' => Post::class,
    'pages' => Page::class,
    'categories' => Category::class,
],
```

Плоское соответствие ключа классу Eloquent-модели, реализующей `Yazar\Contracts\Documentable`:

- **Ключ** (`posts`/`pages`/`categories`) — обходится обобщённо в `DocumentImportService::importAllConfiguredModels()`, `BuildCommand` и `ContentController::show()`. Ключи `posts` и `categories` дополнительно читаются напрямую, по фиксированному имени, в трёх местах `ContentController` (`showCategoryPage()`, `renderMainPage()`, `renderDocument()` — например, `config('yazar.content_types.posts')`). Новый тип контента можно добавить под любым ключом, но переименовывать `posts`/`categories` нельзя без правки этих мест вызова.
- **Значение** — сам класс модели. Обязан реализовывать `Yazar\Contracts\Documentable`: `documentType(): string` (значение, попадающее в колонку `documents.type` — `Document` отклоняет создание/обновление записи, если оно не совпадает), `documentsPath(): string` (подпапка модели на общем диске `content`, см. `disks` ниже) и `exporterClass(): class-string<Exporter>` (логика статического экспорта, которую `php artisan build` использует для этого типа). Можно переопределить на собственный подкласс (`Post`, `Page`, `Category` или их наследник) без изменения кода пакета.

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

Определения Laravel `Storage`-дисков, которые `YazarServiceProvider::boot()` регистрирует во время выполнения (`config(["filesystems.disks.$name" => $diskConfig])`) — они не описываются в стандартном `config/filesystems.php` приложения.

- **`content`** — единый общий диск для всего Markdown-контента, корень — `content_path`. Каждая модель `Documentable` объявляет свою подпапку внутри него через `documentsPath()` (например, `Post::documentsPath()` возвращает `'posts'`); `DocumentImportService` получает список файлов через `Storage::disk('content')->allFiles($modelClass::documentsPath())` и сам отрезает префикс подпапки перед сохранением `path`/`slug` (поэтому URL остаются `/hello-world/`, а не `/posts/hello-world/`). `throw: false` — отсутствие директории на диске не бросает исключение при импорте. Файлы или папки, чьё имя начинается с `#` (например `#draft.md` или `#tools/git.md`), полностью исключаются из импорта — не читаются, не попадают в таблицу `documents` и не публикуются ни статическим билдом, ни динамическим роутингом. Проверяется каждый сегмент пути, поэтому папка с префиксом `#` скрывает всё своё содержимое целиком, на любом уровне вложенности. Это простой способ скрыть черновик поста, страницы или целую подборку контента, не удаляя и не перемещая файлы.
- **`static_output`** — диск, куда `php artisan build` записывает готовые HTML-страницы. `root` — та же директория, что задаётся опцией `output_directory` (см. выше), но читается отдельным вызовом `env('OUTPUT_DIRECTORY', 'build')`. `url`/`visibility: public` нужны, если содержимое этого диска предполагается отдавать напрямую как публичные файлы.

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

- **`extensions`** — список class-string'ов `League\CommonMark\Extension\ExtensionInterface`, которые `Yazar\Markdown\MarkdownParser::__construct()` добавляет в `Environment` поверх обязательного ядра (`CommonMarkCoreExtension` + `FrontMatterExtension`). По умолчанию пуст — без явного добавления класса в этот список поведение рендеринга markdown не меняется.
- **`default_disk`** — диск, который использует `Yazar\Markdown\Extensions\DiskUrlExtension`, когда ссылка `disk://path` не указывает имя диска (см. ниже). По умолчанию `null` — тогда используется собственный диск по умолчанию хост-приложения (`config('filesystems.default')`).
- **`phiki`** — настройки для `Yazar\Markdown\Extensions\PhikiHighlightExtension` (подсветка синтаксиса fenced-код-блоков через библиотеку `phiki/phiki`). Расширение читает эти значения самостоятельно, они не влияют ни на что, пока `PhikiHighlightExtension::class` не добавлен в `extensions` выше.
  - **`theme`** — тема оформления, передаётся в `Phiki::codeToHtml()` (валидные значения — слаги `Phiki\Theme\Theme`, например `'github-light'`, `'dracula'`, `'nord'`).
  - **`default_grammar`** — грамматика, применяемая к fenced-блокам без указанного языка (голый ` ``` ` без слова после бэктиков). Слаг должен совпадать с одним из кейсов `Phiki\Grammar\Grammar` (например, `'shellscript'`, `'php'`, `'json'`).

**Как включить подсветку:** добавить `\Yazar\Markdown\Extensions\PhikiHighlightExtension::class` в `markdown.extensions` в опубликованном `config/yazar.php` хост-приложения. Язык блока берётся из info-string фенса (` ```php ` → `php`); для блоков без языка используется `default_grammar`.

## Ссылки на файлы Laravel-диска из markdown

`Yazar\Markdown\Extensions\DiskUrlExtension` позволяет ссылаться из markdown-контента на файл на **любом** зарегистрированном Laravel-диске — не только на собственных дисках движка (`content`/`static_output`), а на любом ключе из `filesystems.disks`, включая `public`, `s3` или кастомный диск хост-приложения — без хардкода абсолютного URL, который отличался бы между окружениями.

Напишите `disk(имяДиска)://путь/к/файлу.ext` где угодно в документе, и при рендере это превратится в `Storage::disk('имяДиска')->url('путь/к/файлу.ext')`:

```markdown
![Скриншот](disk(s3)://screenshots/dashboard.png)

Полный отчёт — [здесь](disk(media)://reports/2026-q1.pdf).

Можно вставить disk(s3)://screenshots/dashboard.png прямо в предложение,
или внутри сырого HTML: <img src="disk(s3)://screenshots/dashboard.png">.
```

Имя диска можно опустить — `disk://путь/к/файлу.ext` резолвится через `markdown.default_disk` (см. раздел `markdown` выше):

```markdown
![Скриншот](disk://screenshots/dashboard.png)
```

- Работает внутри `![alt](...)`, `[текст](...)`, обычного текста параграфа и сырого HTML — не только внутри синтаксиса ссылок/картинок.
- Не резолвится внутри fenced-код-блоков и инлайн-кода — можно показать синтаксис как пример, не опасаясь что он превратится в реальную ссылку.
- Front matter (YAML-шапка в начале файла) этим расширением **не обрабатывается** — только тело markdown-документа.
- Без ограничивающего списка дисков: если диск не зарегистрирован, либо его драйвер не умеет генерировать URL, резолвинг бросает `Yazar\Markdown\Extensions\DiskUrlResolutionException` (оборачивающее исходную ошибку) вместо тихой подстановки исходного текста. При импорте контента (`DocumentImportService`, используется `php artisan build`) документ с битой disk-ссылкой помечается невалидным, а не роняет весь импорт целиком.

**Как включить:** добавить `\Yazar\Markdown\Extensions\DiskUrlExtension::class` в `markdown.extensions` в опубликованном `config/yazar.php` хост-приложения.

## Ссылки на imgproxy из markdown

`Yazar\Markdown\Extensions\ImgproxyExtension` позволяет вставлять в markdown-контент подписанные ссылки на сервис [imgproxy](https://imgproxy.net/) — синтаксис `imgproxy(SOURCE, 'preset-key')`, где `SOURCE` — либо ссылка вида `disk(имяДиска)://путь` (как в разделе выше), либо произвольный URL как есть:

```markdown
![Обложка](imgproxy(disk(yandex)://images-posts/figma/dashed-line.gif, 'post-cover'))

Можно и с обычным URL: imgproxy(https://example.com/photo.jpg, 'post-cover').
```

`'preset-key'` резолвится через новый блок конфига `config('yazar.imgproxy.presets')` — сырую строку опций обработки imgproxy как есть, без собственного DSL поверх DSL imgproxy:

```php
'imgproxy' => [
    'base_url' => env('IMGPROXY_BASE_URL', 'http://127.0.0.1:6066'),
    'key' => env('IMGPROXY_KEY'),
    'salt' => env('IMGPROXY_SALT'),
    'presets' => [
        'post-cover' => 'rs:fit:1200:630/q:80/f:webp',
    ],
],
```

- **`base_url`** — адрес сервиса imgproxy, подставляется в начало итоговой ссылки.
- **`key`/`salt`** — hex-строки для HMAC-подписи ссылки (см. [документацию imgproxy по генерации ссылок](https://docs.imgproxy.net)). **Должны буквально совпадать** со значениями `IMGPROXY_KEY`/`IMGPROXY_SALT` в `.env` самого сервиса imgproxy — это два разных `.env`-файла в разных репозиториях, синхронизация ручная. Несовпадение не приводит к ошибке на стороне `ImgproxyExtension` (ссылка успешно строится и подписывается по тем ключам, что заданы), а к `403` от самого сервиса imgproxy при переходе по неверно подписанной ссылке.
- **`presets`** — карта `ключ → сырая строка опций imgproxy`. Значение не валидируется против реального синтаксиса опций imgproxy — некорректная строка опций является ответственностью того, кто пишет конфиг.

**Резолвинг `disk(имяДиска)://путь` внутри `imgproxy(...)` — по `driver` диска**, не всегда через `Storage::url()`:
- если у диска `driver: s3` — источником для imgproxy становится `s3://bucket/root/путь`, собранный из конфига диска (без обращения к самому диску и без S3-адаптера в зависимостях пакета) — так работает imgproxy с приватными S3-бакетами без публичного `url`;
- иначе — источником становится `Storage::disk('имяДиска')->url('путь')`, как и в `DiskUrlExtension`.

- Работает в тех же местах документа, что и `DiskUrlExtension`: `![alt](...)`, `[текст](...)`, обычный текст параграфа, сырой HTML.
- Не резолвится внутри fenced-код-блоков и инлайн-кода.
- Front matter этим расширением не обрабатывается.
- Ошибки резолвинга (неизвестный ключ пресета, незарегистрированный диск, некорректные `key`/`salt`) бросают `Yazar\Markdown\Extensions\ImgproxyResolutionException` вместо тихой подстановки. При импорте контента документ с битой `imgproxy(...)`-ссылкой помечается невалидным, как и с битой `disk(...)`-ссылкой.

**Важно про порядок расширений:** если `ImgproxyExtension` и `DiskUrlExtension` подключены одновременно, `ImgproxyExtension` **должна** идти в списке `markdown.extensions` раньше `DiskUrlExtension` — иначе `DiskUrlExtension` испортит вложенный `disk(...)`-вызов внутри `imgproxy(...)` раньше, чем до него доберётся `ImgproxyExtension`.

**Как включить:** добавить `\Yazar\Markdown\Extensions\ImgproxyExtension::class` в `markdown.extensions` (перед `DiskUrlExtension::class`, если она тоже подключена) в опубликованном `config/yazar.php` хост-приложения.

**Хелпер `imgproxy()` для Blade и полей front matter.** `ImgproxyExtension` резолвит `imgproxy(...)` только внутри тела markdown-документа — front matter (например, поле `cover_image` в YAML-шапке) этим расширением не обрабатывается (см. выше). Для таких случаев доступна глобальная функция `imgproxy(string $source, string $presetKey): string`, которая строит и подписывает ту же ссылку напрямую из PHP/Blade, используя тот же `SOURCE` (`disk(имяДиска)://путь` или голый URL) и тот же `config('yazar.imgproxy.presets')`:

```blade
@if($page->meta->cover_image)
    <img src="{{ imgproxy('disk(yandex)://'.$page->meta->cover_image, 'post-cover') }}">
@endif
```

Хелпер не требует, чтобы `ImgproxyExtension::class` была подключена в `markdown.extensions` — конфиг `yazar.imgproxy` (`base_url`/`key`/`salt`/`presets`) используется напрямую.

## Смотри также

- [Harness](harness.md) — как локально проверить конфигурацию на реальном Laravel-приложении
- [Консольные команды](../../README.ru.md#консольные-команды) — команды `build` и `yazar:install`, которые читают эти опции
