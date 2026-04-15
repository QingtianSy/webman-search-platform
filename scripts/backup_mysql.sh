#!/bin/sh
set -eu

BASE_DIR=/var/www/search-platform
BACKUP_DIR=$BASE_DIR/backups
NOW=$(date +%Y%m%d_%H%M%S)
mkdir -p "$BACKUP_DIR"

mysqldump -uroot -p"${MYSQL_PASSWORD:-}" search_platform > "$BACKUP_DIR/mysql_${NOW}.sql"
