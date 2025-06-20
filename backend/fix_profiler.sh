#!/bin/bash

# Script to fix profiler issues and clean cache
# Run this if you encounter null byte errors

echo "🔧 Fixing Symfony Profiler Issues..."

# Clear all cache
echo "1. Clearing cache..."
rm -rf /var/www/html/iamgickpro/backend/var/cache/*
rm -rf /var/www/html/iamgickpro/backend/var/log/dev.log

# Create necessary directories
echo "2. Creating cache directories..."
mkdir -p /var/www/html/iamgickpro/backend/var/cache/dev
mkdir -p /var/www/html/iamgickpro/backend/var/cache/prod
mkdir -p /var/www/html/iamgickpro/backend/var/log

# Set proper permissions
echo "3. Setting permissions..."
chmod -R 755 /var/www/html/iamgickpro/backend/var/
chown -R www-data:www-data /var/www/html/iamgickpro/backend/var/ 2>/dev/null || echo "Note: Could not change ownership (this is normal in some environments)"

# Clear any potentially corrupted session files
echo "4. Clearing sessions..."
rm -rf /var/www/html/iamgickpro/backend/var/sessions/* 2>/dev/null || echo "No session files to clear"

# Check for and remove any files with null bytes
echo "5. Checking for problematic files..."
find /var/www/html/iamgickpro/backend/var/ -name "*" -exec sh -c 'if echo "$1" | grep -q "\x00"; then echo "Removing file with null bytes: $1"; rm -f "$1"; fi' _ {} \; 2>/dev/null

# Clear Symfony cache properly
echo "6. Clearing Symfony cache..."
cd /var/www/html/iamgickpro/backend
php bin/console cache:clear --env=dev --no-warmup

# Warm up cache
echo "7. Warming up cache..."
php bin/console cache:warmup --env=dev

echo "✅ Profiler cleanup completed!"
echo ""
echo "Configuration applied:"
echo "  - Profiler limited to main requests only"
echo "  - Reduced data collection to minimize I/O"
echo "  - Error handling for profiler issues"
echo ""
echo "If you still encounter issues, consider:"
echo "1. Temporarily disabling the profiler: APP_ENV=prod in .env"
echo "2. Running: php bin/console cache:clear --no-warmup"
echo "3. Checking disk space: df -h"
echo "4. Restarting the web server"
