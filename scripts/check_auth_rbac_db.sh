#!/bin/sh
set -eu

BACKEND_DIR=${1:-/var/www/search-platform/backend}
cd "$BACKEND_DIR"

php -r 'require "vendor/autoload.php"; require "support/helpers.php"; $pdo = \support\adapter\MySqlClient::pdo(); if(!$pdo){fwrite(STDERR, "mysql connection failed\n"); exit(1);} echo "mysql connection ok\n";'
