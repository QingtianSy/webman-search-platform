#!/bin/sh
set -eu

BASE_DIR=/var/www/search-platform
BACKEND_DIR=$BASE_DIR/backend
SERVICE_NAME=webman-search-platform

cd "$BACKEND_DIR"

if [ -f .env.example ] && [ ! -f .env ]; then
  cp .env.example .env
fi

systemctl daemon-reload
systemctl restart "$SERVICE_NAME"
systemctl status "$SERVICE_NAME" --no-pager || true
