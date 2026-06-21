#!/bin/bash
# Simple deploy script (replaces Forge deploy button)
# Usage: ./deploy.sh

set -e

cd /home/forge/t.leaguesofcode.space

echo "Pulling latest changes..."
git pull origin master

echo "Installing composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "Building frontend assets..."
npm ci
npm run build

echo "Running migrations..."
php artisan migrate --force

echo "Optimizing application caches..."
php artisan optimize

echo "Restarting PHP-FPM..."
sudo /usr/sbin/service php8.4-fpm reload

echo "Deploy complete!"
