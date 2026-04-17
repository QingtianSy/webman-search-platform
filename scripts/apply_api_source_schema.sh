#!/bin/sh
set -eu

BACKEND_DIR=${1:-/var/www/search-platform/backend}
cd "$BACKEND_DIR"

php -r 'require "vendor/autoload.php"; require "support/helpers.php"; $file = __DIR__ . "/database/migrations/0005_api_source_schema.sql"; $ok = \support\adapter\MySqlClient::executeSqlFile($file); if(!$ok){fwrite(STDERR, "apply api_source schema failed\n"); exit(1);} echo "api_source schema applied\n";'
