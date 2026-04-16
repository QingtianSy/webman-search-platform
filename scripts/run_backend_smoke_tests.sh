#!/bin/sh
set -eu

BACKEND_DIR=${1:-/var/www/search-platform/backend}
cd "$BACKEND_DIR"

php tests/auth_mock_smoke.php
php tests/auth_admin_mock_smoke.php
php tests/search_mock_smoke.php
php tests/dashboard_mock_smoke.php
php tests/question_detail_mock_smoke.php
php tests/apikey_mock_smoke.php
php tests/doc_config_mock_smoke.php
php tests/collect_task_detail_mock_smoke.php
php tests/menu_mock_smoke.php
php tests/system_config_mock_smoke.php
php tests/rbac_mock_smoke.php
php tests/health_mock_smoke.php
php tests/user_center_mock_smoke.php
php tests/user_center_real_ready_smoke.php

echo 'all backend smoke tests passed'
