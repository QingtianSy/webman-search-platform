#!/bin/sh
set -eu

cd /var/www/search-platform/backend

if [ ! -f composer.json ]; then
  echo 'composer.json not found'
  exit 1
fi

composer install --no-dev --optimize-autoloader

echo 'Composer dependencies prepared.'
