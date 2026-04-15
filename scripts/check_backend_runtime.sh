#!/bin/sh
set -eu

BACKEND_DIR=${1:-/var/www/search-platform/backend}
cd "$BACKEND_DIR"

echo '== check php =='
if command -v php >/dev/null 2>&1; then
  php -v | sed -n '1,2p'
else
  echo 'php not found'
fi

echo '== check composer =='
if command -v composer >/dev/null 2>&1; then
  composer --version
else
  echo 'composer not found'
fi

echo '== check files =='
[ -f composer.json ] && echo 'composer.json ok' || echo 'composer.json missing'
[ -f .env ] && echo '.env exists' || echo '.env missing'
[ -f vendor/autoload.php ] && echo 'vendor autoload exists' || echo 'vendor autoload missing'
