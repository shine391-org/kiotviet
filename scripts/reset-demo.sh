#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "[reset-demo] Stopping containers & removing volumes..."
docker-compose down -v

echo "[reset-demo] Starting db for fresh import..."
docker-compose up -d db

echo "[reset-demo] Waiting for MySQL to be ready..."
until docker exec meomeo2-db-1 mysqladmin ping -h "localhost" --silent; do
  sleep 2
done

echo "[reset-demo] Starting API, FE and phpMyAdmin..."
docker-compose up -d api fe phpmyadmin

echo "[reset-demo] Seeding demo data..."
docker exec meomeo2-api-1 php spark db:seed DevDemoSeeder || true

echo "[reset-demo] Done. API: http://localhost:8000  phpMyAdmin: http://localhost:8080"
