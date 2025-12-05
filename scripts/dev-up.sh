#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "[dev-up] Starting full stack (web, db, phpmyadmin, fe)..."
docker-compose up -d web db phpmyadmin fe

if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
fi

echo "[dev-up] Logs (tail) use: docker-compose logs -f web fe db phpmyadmin"
echo "API: http://localhost:${APP_PORT:-8000} | FE: http://localhost:${FE_PORT:-3000} | phpMyAdmin: http://localhost:${PMA_PORT:-8080}"
