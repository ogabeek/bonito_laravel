#!/bin/bash
# Simple deploy script (replaces Forge deploy button)
# Usage: ./deploy.sh

set -e

cd /home/forge/t.leaguesofcode.space

echo "Pulling latest changes..."
git pull origin master

echo "Installing composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "Running migrations..."
php artisan migrate --force

echo "Clearing caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Restarting PHP-FPM..."
sudo /usr/sbin/service php8.4-fpm reload

echo "Deploy complete!"
