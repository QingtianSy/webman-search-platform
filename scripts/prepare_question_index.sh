#!/bin/sh
set -eu

ES_HOST=${1:-http://127.0.0.1:9200}
INDEX_NAME=${2:-question_index}

curl -sS -X PUT "$ES_HOST/$INDEX_NAME" \
  -H 'Content-Type: application/json' \
  -d '{
    "mappings": {
      "properties": {
        "question_id": {"type": "keyword"},
        "md5": {"type": "keyword"},
        "stem": {"type": "text"},
        "stem_plain": {"type": "text"},
        "options_text": {"type": "text"},
        "answer_text": {"type": "text"},
        "analysis": {"type": "text"},
        "type_code": {"type": "keyword"},
        "type_name": {"type": "keyword"},
        "source_id": {"type": "keyword"},
        "source_name": {"type": "keyword"},
        "category_id": {"type": "keyword"},
        "category_name": {"type": "keyword"},
        "tags": {"type": "keyword"},
        "status": {"type": "integer"},
        "created_at": {"type": "date"}
      }
    }
  }'

echo
