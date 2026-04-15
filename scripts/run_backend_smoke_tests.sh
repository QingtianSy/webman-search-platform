#!/bin/sh
set -eu

BACKEND_DIR=${1:-/var/www/search-platform/backend}
cd "$BACKEND_DIR"

php tests/auth_mock_smoke.php
php tests/search_mock_smoke.php
php tests/dashboard_mock_smoke.php

echo 'all backend smoke tests passed'
