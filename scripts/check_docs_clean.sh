#!/bin/sh
set -eu

TARGET=${1:-.}

hits=$(grep -RIl '\[CONTEXT OFFLOADED\]' "$TARGET" 2>/dev/null | grep -v vendor | grep -v node_modules | grep -v _refs | grep -v '.git/' || true)
if [ -n "$hits" ]; then
  echo "$hits"
  echo 'found corrupted markdown/content placeholders'
  exit 1
fi

echo 'docs clean'
