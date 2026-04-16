#!/bin/sh
set -eu

ROOT=${1:-.}
cd "$ROOT"

echo '== php lint =='
find backend -type f -name '*.php' | while read -r f; do php -l "$f" >/dev/null; done

echo '== shell syntax =='
find . -type f -name '*.sh' | while read -r f; do /bin/sh -n "$f"; done

echo '== python syntax =='
find . -type f -name '*.py' | while read -r f; do python3 -m py_compile "$f"; done

echo '== json validity =='
find . -type f -name '*.json' | while read -r f; do python3 -m json.tool "$f" >/dev/null 2>&1; done

echo '== docs contamination =='
sh scripts/check_docs_clean.sh .

echo '== backend composer validate =='
cd backend
composer validate --no-check-publish >/dev/null
composer smoke >/dev/null
cd ..

echo '== frontend static structure =='
[ -f frontend/package.json ]
[ -f frontend/vite.config.ts ]
[ -f frontend/tsconfig.json ]
[ -f frontend/src/env.d.ts ]

echo 'repo quality check passed'
