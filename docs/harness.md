[← Назад к README](../README.md)

# Harness: ручная проверка пакета

Yazar — голый Composer-пакет: в репозитории нет `app/`, `bootstrap/`, `public/`, `artisan`, поэтому его нельзя запустить как приложение прямо здесь. **Harness** — это Laravel-приложение в подпапке `harness/`, которое даёт такую возможность локально, не превращая репозиторий пакета обратно в Laravel-скелет.

## Что это и почему так

- `harness/` создаётся и переиспользуется скриптом `bin/harness-init.sh` — не хранится в git (`.gitignore` содержит `/harness`).
- Подключает `seryak/yazar` через Composer path-repository с `symlink: true` на корень репозитория (`../` относительно `harness/`) — правки в `src/` пакета видны в harness мгновенно, без переустановки зависимости.
- Переиспользуемый, а не одноразовый: `bin/harness-init.sh` можно запускать повторно — при существующей `harness/` он не пересоздаёт Laravel-приложение с нуля, а только обновляет в нём зависимость `seryak/yazar`.

Это тот же механизм (path-repository + `symlink: true`), которым пакет устанавливается в README у [реального стороннего потребителя](../README.md#installation) — разница только в том, что harness живёт внутри самого репозитория пакета и предназначен для разработчика, а не для конечного пользователя.

## Предварительные требования

- PHP 8.2+ и Composer 2.x (те же, что нужны самому пакету).
- Доступ к сети для `composer create-project laravel/laravel` при первом запуске.

## Быстрый старт

```bash
bin/harness-init.sh
cd harness
php artisan yazar:install
php artisan migrate
php artisan build
php artisan serve
```

- `bin/harness-init.sh` — создаёт `harness/` (если её ещё нет) или обновляет в ней `seryak/yazar` (если уже есть).
- `php artisan yazar:install` — публикует `config/yazar.php`, дефолтные вьюхи и demo-контент; никогда не трогает базу данных.
- `php artisan migrate` — создаёт таблицу `documents`.
- `php artisan build` — импортирует контент и рендерит статический сайт.
- `php artisan serve` — поднимает harness как обычное Laravel-приложение для просмотра в браузере.

## Что делает `bin/harness-init.sh`

```bash
if [ ! -d harness ]; then
    composer create-project laravel/laravel harness "^12.0"
    composer config --working-dir=harness repositories.yazar '{"type":"path","url":"../","options":{"symlink":true}}' --json
    composer require --working-dir=harness seryak/yazar:dev-master
else
    composer update --working-dir=harness seryak/yazar
fi
```

- **Первый запуск** (`harness/` отсутствует): создаёт Laravel-приложение через `composer create-project laravel/laravel harness "^12.0"`, прописывает path-repository с `symlink: true` одним JSON-вызовом `composer config`, затем требует `seryak/yazar:dev-master`.
- **Повторный запуск** (`harness/` уже существует): не пересоздаёт приложение — обновляет только зависимость `seryak/yazar`, оставляя остальной harness (базу, публикованные файлы, `.env`) как есть.

> **Версия `"^12.0"` обязательна.** Она совпадает со старшей версией `illuminate/*`, которую требует `composer.json` пакета. Без неё `composer create-project` ставит последнюю версию Laravel вообще (на момент написания — 13.x), а `composer require seryak/yazar:dev-master` в такой harness падает с ошибкой resolve-конфликта (`illuminate/console ^12.0` vs уже установленный `laravel/framework ^13.x`). Если пакет когда-нибудь перейдёт на Laravel 13, эту версию в скрипте нужно поднять вместе с `composer.json`.

## Сброс harness

`harness/` безопасно удалять в любой момент — она не в git и не содержит ничего, что нужно репозиторию пакета:

```bash
rm -rf harness
bin/harness-init.sh
```

Полезно, если harness оказался в неконсистентном состоянии (например, `composer.json` внутри неё был испорчен вручную, или она была создана до фикса версии Laravel и содержит несовместимый `laravel/framework`) — скрипт не чинит существующую конфигурацию, только переиспользует её или создаёт с нуля.

## Ограничения

- Harness предназначен только для **ручной** проверки пакета (`yazar:install`, `migrate`, `build`, `serve`) — он не прогоняет существующий набор PHPUnit-тестов пакета (`tests/`). Тесты физически лежат в репозитории, но не запускаются здесь: они завязаны на `bootstrap/app.php`, которого в голом пакете больше нет, а подключать их через harness сознательно не стали — это привязало бы тесты к конкретному локальному пути и сломало бы их в CI.
- Harness — не прототип и не черновик будущего личного сайта на движке Yazar; это исключительно инструмент разработчика для проверки самого пакета.

## Смотри также

- [Установка пакета в стороннем приложении](../README.md#installation) — тот же механизм path-repository, но для реального потребителя пакета
- [Console commands](../README.md#console-commands) — команды `build` и `yazar:install`, которые используются внутри harness
- [Configuration](../README.md#configuration) — что публикует `yazar:install` и как настраивается `config/yazar.php`
