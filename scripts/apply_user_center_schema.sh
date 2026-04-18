#!/bin/sh
set -eu

BACKEND_DIR=${1:-/var/www/search-platform/backend}
cd "$BACKEND_DIR"

php -r 'require "vendor/autoload.php"; require "support/helpers.php"; $file = __DIR__ . "/database/migrations/0003_user_center_schema.sql"; $ok = \support\adapter\MySqlClient::executeSqlFile($file); if(!$ok){fwrite(STDERR, "apply user_center schema failed\n"); exit(1);} echo "user_center schema applied\n";'
