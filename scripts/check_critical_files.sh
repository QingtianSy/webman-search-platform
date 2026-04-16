#!/bin/sh
set -eu

ROOT=${1:-.}
cd "$ROOT"

required_files="
backend/start.php
backend/support/bootstrap.php
backend/support/helpers.php
backend/config/app.php
backend/config/route.php
backend/config/server.php
backend/public/README.md
backend/runtime/logs/README.md
frontend/package.json
frontend/src/router/index.ts
frontend/src/layouts/AppLayout.vue
"

for f in $required_files; do
  if [ ! -f "$f" ]; then
    echo "missing: $f"
    exit 1
  fi
done

echo 'critical files ok'
