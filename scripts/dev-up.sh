#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "[dev-up] Starting full stack (api, db, phpmyadmin, fe)..."
docker-compose up -d api db phpmyadmin fe

echo "[dev-up] Logs (tail) use: docker-compose logs -f api fe db phpmyadmin"
echo "API: http://localhost:8000 | FE: http://localhost:3000 | phpMyAdmin: http://localhost:8080"
