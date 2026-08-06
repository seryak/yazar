# Yazar

*[English](README.md)*

Yazar — движок генератора статических сайтов для Laravel: Markdown-контент с front matter импортируется в Eloquent-документы и рендерится через Blade-шаблоны — либо динамически по HTTP, либо в виде заранее собранного статического HTML.

Для Laravel-разработчиков, которые хотят:

- Использовать привычные инструменты: Eloquent, Blade, Service Container, Facades.
- Расширять поведение через конфиг, а не форком движка.
- Не дублировать логику Markdown → HTML между статическим и динамическим рендерингом.
- Получить скорость статического сайта без отдельного языка шаблонов.

Этот репозиторий — только движок: Composer-пакет, а не самостоятельное приложение. Он предназначен для установки внутри вашего Laravel-проекта.

## Установка

Пока пакет не тегирован, устанавливайте его как локальную path-зависимость с symlink, чтобы изменения в движке сразу подхватывались использующим его приложением:

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

Когда появятся версионированные релизы, это станет обычным `composer require seryak/yazar`.

## Начало работы

```bash
php artisan yazar:install   # публикует config/yazar.php, шаблоны по умолчанию и демо-контент
php artisan migrate         # создаёт таблицу documents
php artisan build           # генерирует статический сайт
```

`php artisan yazar:install` никогда не трогает базу данных — выполнение миграций всегда отдельный, явный шаг.

## Конфигурация

Вся конфигурация движка находится в опубликованном `config/yazar.php` — что означает каждая опция, см. в [docs/ru/configuration.md](docs/ru/configuration.md).

Выбор view для конкретного документа настраивается не здесь: он берётся из поля front matter `view::extends` в каждом Markdown-файле.

## Консольные команды

- `php artisan build` — импортирует весь настроенный контент, рендерит его в статический HTML и (если задан `deploy_target`) копирует результат туда.
- `php artisan yazar:install` — публикует конфиг, шаблоны по умолчанию и демо-контент; поддерживает `--force` для перезаписи.
- `php artisan yazar:clear-imgproxy-cache` — удаляет все файлы, закешированные `ImgproxyBuildResolver` во время статической сборки (см. [документацию по конфигурации](docs/ru/configuration.md#кеширование-imgproxy-ссылок-в-статической-сборке)).

## Локальная разработка

У самого движка нет запускаемого Laravel-приложения. `bin/harness-init.sh`
создаёт (или переиспользует) игнорируемое git-ом приложение `harness/` внутри
этого репозитория для ручного тестирования пакета — см.
[docs/ru/harness.md](docs/ru/harness.md) о настройке, использовании и сбросе.

---

## Документация

| Раздел | Описание |
|-------|-------------|
| [Шаблоны](docs/ru/templates.md) | Поля front matter и переменные Blade-шаблонов |
| [URL](docs/ru/urls.md) | Как `url` документа вычисляется из `permalink()`, `slug` и front matter |
| [Конфигурация](docs/ru/configuration.md) | Что означает каждая опция `config/yazar.php` |
| [Типы контента](docs/ru/content-types.md) | Добавление нового типа документа помимо posts/pages/categories |
| [Harness](docs/ru/harness.md) | Локальное, игнорируемое git-ом Laravel-приложение для ручного тестирования пакета |
