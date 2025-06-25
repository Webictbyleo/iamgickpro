#!/bin/bash

# Test script to verify installation fixes
cd /var/www/html/iamgickpro/backend

echo "Testing PSR-4 autoloading..."

# Create a temporary .env.local for testing
cat > .env.local << 'EOF'
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=temp_test_secret_12345
DATABASE_URL="mysql://root:password@127.0.0.1:3306/testdb?serverVersion=8.0&charset=utf8mb4"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=temp_passphrase
REDIS_URL=redis://localhost:6379
MAILER_DSN=smtp://localhost:1025
REPLICATE_API_TOKEN=test_token
YOUTUBE_API_KEY=test_key
MAX_UPLOAD_SIZE=104857600
ALLOWED_MIME_TYPES=image/jpeg,image/png,image/gif,image/webp,image/svg+xml,video/mp4,video/webm
FRONTEND_URL=https://test.com
CORS_ALLOW_ORIGIN=https://test.com
EOF

echo "Created test .env.local file"

echo "Testing composer autoload generation..."
APP_ENV=prod composer dump-autoload --optimize --no-dev 2>&1 | tee autoload_test.log

echo "Checking for PSR-4 violations..."
if grep -q "does not comply with psr-4 autoloading standard" autoload_test.log; then
    echo "❌ PSR-4 violations still present!"
    grep "does not comply with psr-4 autoloading standard" autoload_test.log
    exit 1
else
    echo "✅ No PSR-4 violations found"
fi

echo "Testing cache clear in production mode..."
APP_ENV=prod php bin/console cache:clear --env=prod 2>&1 | tee cache_test.log

echo "Checking for DebugBundle errors..."
if grep -q "DebugBundle" cache_test.log; then
    echo "❌ DebugBundle error still present!"
    grep "DebugBundle" cache_test.log
    exit 1
else
    echo "✅ No DebugBundle errors found"
fi

echo "All tests passed! ✅"

# Clean up
rm -f .env.local autoload_test.log cache_test.log
