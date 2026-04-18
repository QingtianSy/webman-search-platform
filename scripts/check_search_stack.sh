#!/bin/sh
set -eu

ES_HOST=${1:-http://127.0.0.1:9200}
REDIS_HOST=${2:-127.0.0.1}
REDIS_PORT=${3:-6379}
MONGO_URI=${4:-mongodb://127.0.0.1:27017}

echo '== elasticsearch =='
curl -sS "$ES_HOST" | sed -n '1,5p' || true

echo '\n== redis =='
if command -v redis-cli >/dev/null 2>&1; then
  redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" ping || true
else
  echo 'redis-cli not found'
fi

echo '\n== mongo =='
if command -v mongosh >/dev/null 2>&1; then
  mongosh "$MONGO_URI" --eval 'db.runCommand({ ping: 1 })' || true
else
  echo 'mongosh not found'
fi
