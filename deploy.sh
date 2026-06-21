#!/bin/bash
# Deploy script for t.leaguesofcode.space (Boniato School Management)
# Usage: ./deploy.sh
#
# Safety model:
#   * The site is in maintenance mode for the whole deploy (no half-applied
#     state is ever served to users).
#   * A gzipped DB backup is taken and integrity-checked BEFORE any code or
#     schema change. The deploy aborts if the backup fails.
#   * Frontend assets are rebuilt so CSS/JS changes actually ship.
#   * The error-prone steps (composer, npm) run BEFORE migrations, so a
#     failure there leaves the database untouched.
#   * On ANY failure the script aborts and INTENTIONALLY leaves the site in
#     maintenance mode. Recovery: inspect the error, fix or roll back, then
#     run `php artisan up`.

set -euo pipefail

SITE_DIR="/home/forge/t.leaguesofcode.space"
BACKUP_DIR="/home/forge/backups"
cd "$SITE_DIR"

CNF=""
cleanup() { [ -n "$CNF" ] && rm -f "$CNF"; }   # never leave the DB password temp file behind
trap cleanup EXIT

on_error() {
    echo ""
    echo "!! DEPLOY FAILED (line $1). The site is still in MAINTENANCE MODE."
    echo "   Pre-deploy DB backup is in: $BACKUP_DIR"
    echo "   Fix the issue or restore the previous revision and backup, then:"
    echo "     php artisan up"
}
trap 'on_error $LINENO' ERR

echo "==> Enabling maintenance mode..."
php artisan down --retry=15 || true

echo "==> Backing up database (pre-deploy)..."
mkdir -p "$BACKUP_DIR"
DB_DATABASE=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"')
DB_USERNAME=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"')
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"')
DB_HOST=$(grep -E '^DB_HOST=' .env | cut -d= -f2- | tr -d '"')
CNF=$(mktemp); chmod 600 "$CNF"
printf '[client]\nhost=%s\nuser=%s\npassword=%s\n' "$DB_HOST" "$DB_USERNAME" "$DB_PASSWORD" > "$CNF"
BACKUP_FILE="$BACKUP_DIR/predeploy-$(date +%Y%m%d-%H%M%S)-${DB_DATABASE}.sql.gz"
mysqldump --defaults-extra-file="$CNF" --single-transaction --quick \
    --routines --triggers --events "$DB_DATABASE" | gzip > "$BACKUP_FILE"
rm -f "$CNF"; CNF=""
gzip -t "$BACKUP_FILE"   # abort the deploy if the backup is corrupt
echo "    backup OK: $BACKUP_FILE"
find "$BACKUP_DIR" -type f -name 'predeploy-*.sql.gz' -mtime +30 -delete

echo "==> Pulling latest changes..."
git pull --ff-only origin master

echo "==> Installing composer dependencies (production)..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "==> Building frontend assets..."
npm ci --no-audit --no-fund
npm run build

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Rebuilding caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Reloading PHP-FPM..."
sudo /usr/sbin/service php8.4-fpm reload

echo "==> Disabling maintenance mode..."
php artisan up

trap - ERR
echo "Deploy complete!"
