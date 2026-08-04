#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."   # корень репозитория пакета

if [ ! -d harness ]; then
    composer create-project laravel/laravel harness "^12.0"
    composer config --working-dir=harness repositories.yazar '{"type":"path","url":"../","options":{"symlink":true}}' --json
    composer require --working-dir=harness seryak/yazar:dev-master -W
    php harness/artisan migrate --force

    for dir in _content resources; do
        if [ -d "$dir" ]; then
            cp -rT "$dir" "harness/$dir"
        fi
    done

    php harness/artisan vendor:publish --tag=yazar-config --force
    (cd harness && npm i && npm run build && php artisan build)
else
    composer update --working-dir=harness seryak/yazar -W
fi

mkdir -p harness/app/Console/Commands
cp bin/dev/*.php harness/app/Console/Commands/
