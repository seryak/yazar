#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."   # корень репозитория пакета

if [ ! -d harness ]; then
    composer create-project laravel/laravel harness "^12.0"
    composer config --working-dir=harness repositories.yazar '{"type":"path","url":"../","options":{"symlink":true}}' --json
    composer require --working-dir=harness seryak/yazar:dev-master
else
    composer update --working-dir=harness seryak/yazar
fi
