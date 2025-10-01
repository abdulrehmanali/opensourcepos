#!/bin/sh
set -e

cd /app

echo "[dev-entrypoint] Installing PHP dependencies (composer install)..."
composer install --no-interaction --prefer-dist --optimize-autoloader

if [ -f package.json ]; then
  echo "[dev-entrypoint] Installing Node dependencies..."
  if [ -f package-lock.json ] || [ -f npm-shrinkwrap.json ]; then
    npm ci || npm install
  else
    npm install
  fi
  echo "[dev-entrypoint] Building frontend assets (npm run build)..."
  npm run build || echo "[dev-entrypoint] No build script or build failed; continuing."
fi

echo "[dev-entrypoint] Starting Apache..."
exec apache2-foreground
