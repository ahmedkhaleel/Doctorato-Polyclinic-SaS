#!/bin/bash
# Aura Derma Clinic - Deployment Script
# Run on cPanel server: bash deploy.sh

set -e

echo "🚀 Starting deployment..."

cd ~/public_html

# Pull latest changes
echo "📥 Pulling latest changes..."
git pull origin main

# Install/update composer dependencies
echo "📦 Installing dependencies..."
composer install --optimize-autoloader --no-dev 2>/dev/null || echo "⚠️  Composer not available, skipping..."

# Clear and rebuild caches
echo "🔧 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "⚡ Building caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure storage link exists
php artisan storage:link 2>/dev/null || true

# Ensure proper permissions
chmod -R 775 storage bootstrap/cache

echo "✅ Deployment complete!"
