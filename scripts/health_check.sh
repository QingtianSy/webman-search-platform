#!/bin/sh
set -eu

BASE_URL=${1:-http://127.0.0.1}

echo "== health =="
wget -qO- "$BASE_URL/health" || true
echo "\n== ready =="
wget -qO- "$BASE_URL/ready" || true
echo
