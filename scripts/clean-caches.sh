#!/usr/bin/env bash
#
# Quick cache reset for BE (CodeIgniter) + FE (Vite/Vitest).
# Usage: ./scripts/clean-caches.sh [--docker]
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "🧹 Cleaning backend cache/logs..."
BE_WRITABLE="$ROOT/backend-ci/writable"
mkdir -p "$BE_WRITABLE/cache" "$BE_WRITABLE/logs"
rm -rf "$BE_WRITABLE/cache/"* "$BE_WRITABLE/logs/"* || true

echo "🧹 Cleaning frontend Vite/Vitest cache..."
FE_ROOT="$ROOT/lanocrm"
rm -rf "$FE_ROOT/node_modules/.vite" "$FE_ROOT/.vitest" || true

if [[ "${1-}" == "--docker" ]]; then
  echo "🐳 Cleaning caches inside container meomeo2-api-1..."
  docker exec meomeo2-api-1 sh -lc 'rm -rf writable/cache/* writable/logs/* && mkdir -p writable/cache writable/logs && chmod -R 777 writable' || true
fi

echo "✅ Cache clean completed."
