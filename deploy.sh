#!/usr/bin/env bash
set -e

# Warna terminal
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}=== Memulai Deployment Ocean Lombok Tour ke VPS ===${NC}"

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REMOTE_HOST="vps1"
REMOTE_DIR="/var/www/ocean-lombok-tour"

echo -e "${YELLOW}[1/4] Menjalankan test di lokal...${NC}"
cd "$PROJECT_DIR"
php artisan test

echo -e "${YELLOW}[2/4] Melakukan build aset frontend lokal (opsional)...${NC}"
npm run build

echo -e "${YELLOW}[3/4] Melakukan sinkronisasi file ke VPS...${NC}"
rsync -avz --delete \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.env' \
    --exclude='.DS_Store' \
    --exclude='.phpunit.result.cache' \
    --exclude='database/database.sqlite' \
    --exclude='bootstrap/cache/*.php' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/framework/cache/data/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/app/public/*' \
    "$PROJECT_DIR/" "$REMOTE_HOST:$REMOTE_DIR/"

echo -e "${YELLOW}[4/4] Menjalankan migrasi, perizinan, dan refresh cache di VPS...${NC}"
ssh "$REMOTE_HOST" "bash -s" << 'EOF'
set -e
cd /var/www/ocean-lombok-tour

# Bersihkan bootstrap cache lama agar package discover selalu akurat
rm -f bootstrap/cache/*.php
php artisan package:discover --ansi

# Jalankan migrasi database
php artisan migrate --force

# Refresh konfigurasi dan cache Laravel
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Pastikan izin file untuk web server www-data
sudo chown -R ubuntu:www-data /var/www/ocean-lombok-tour
sudo chmod -R 775 storage bootstrap/cache
EOF

echo -e "${GREEN}=== Deployment Berhasil! Website Aktif: https://olt.web.id ===${NC}"
