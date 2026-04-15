#!/bin/sh
set -eu

mongodump --db search_platform --out /var/www/search-platform/backups/mongo_$(date +%Y%m%d_%H%M%S)
