#!/usr/bin/env bash
set -e

echo "Bash script"

sudo chown www-data:www-data /var/www/project/*

composer install

exec "$@"

