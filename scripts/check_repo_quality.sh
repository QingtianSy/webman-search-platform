#!/bin/sh
set -eu

REPO_DIR=${1:-/var/minis/workspace/webman-search-platform}
cd "$REPO_DIR"

echo '== php lint =='
find backend -type f -name '*.php' | sort > /tmp/repo_php_files.txt
while IFS= read -r f; do php -l "$f" >/dev/null; done < /tmp/repo_php_files.txt

echo '== shell syntax =='
find . -type f -name '*.sh' | sort > /tmp/repo_sh_files.txt
while IFS= read -r f; do /bin/sh -n "$f"; done < /tmp/repo_sh_files.txt

echo '== python syntax =='
find . -type f -name '*.py' | sort > /tmp/repo_py_files.txt
while IFS= read -r f; do python3 -m py_compile "$f"; done < /tmp/repo_py_files.txt

echo '== json validity =='
find . -type f -name '*.json' | sort > /tmp/repo_json_files.txt
while IFS= read -r f; do python3 -m json.tool "$f" >/dev/null 2>&1; done < /tmp/repo_json_files.txt

echo '== backend composer validate =='
cd backend
composer validate --no-check-publish >/dev/null
composer smoke
cd ..

echo '== frontend static structure =='
[ -f frontend/package.json ]
[ -f frontend/vite.config.ts ]
[ -f frontend/tsconfig.json ]
[ -f frontend/src/env.d.ts ]

echo 'repo quality check passed'
