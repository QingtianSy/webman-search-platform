#!/bin/sh
set -eu

BACKEND_DIR=${1:-/var/www/search-platform/backend}
cd "$BACKEND_DIR"

php -r 'require "vendor/autoload.php"; require "support/helpers.php"; $file = __DIR__ . "/database/seeds/0002_user_center_seed.sql"; $ok = \support\adapter\MySqlClient::executeSqlFile($file); if(!$ok){fwrite(STDERR, "apply user_center seed failed\n"); exit(1);} echo "user_center seed applied\n";'
