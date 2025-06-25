#!/bin/bash

# Production Migration Strategy for Design Platform
# This script handles the migration deployment for production environments

set -e

echo "🚀 Starting Production Migration Deployment"
echo "============================================"

# Check if we're in the correct directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: Must be run from the backend directory"
    exit 1
fi

# Check environment
if [ "$APP_ENV" != "prod" ]; then
    echo "⚠️  Warning: APP_ENV is not set to 'prod'. Current: $APP_ENV"
    read -p "Continue anyway? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo "📋 Migration Strategy for Production"
echo "==================================="
echo "This deployment uses a comprehensive migration approach:"
echo "- Version20250624000000.php: Complete schema creation"
echo "- This migration creates all tables and relationships"
echo "- Safe for fresh production deployments"
echo

# Create database if it doesn't exist
echo "🗄️  Creating database (if not exists)..."
php bin/console doctrine:database:create --if-not-exists --env=prod

# Check current migration status
echo "📊 Checking migration status..."
php bin/console doctrine:migrations:status --env=prod

echo
echo "🔍 Available migrations in this deployment:"
ls -la migrations/

echo
echo "📝 Migration deployment options:"
echo "1. Fresh deployment (recommended for new production)"
echo "2. Mark existing migrations as executed (if upgrading existing DB)"
echo "3. Selective migration execution"
echo

read -p "Choose deployment option (1/2/3): " -n 1 -r
echo

case $REPLY in
    1)
        echo "🚀 Executing fresh deployment..."
        echo "This will run all available migrations"
        php bin/console doctrine:migrations:migrate --no-interaction --env=prod
        ;;
    2)
        echo "🏷️  Marking comprehensive migration as executed..."
        echo "This marks the complete schema migration as executed without running it"
        echo "Use this if your production database already has all the tables"
        php bin/console doctrine:migrations:version Version20250624000000 --add --no-interaction --env=prod
        
        # Also mark the older migrations as executed
        echo "Marking other available migrations as executed..."
        php bin/console doctrine:migrations:version Version20250530114718 --add --no-interaction --env=prod || true
        php bin/console doctrine:migrations:version Version20250531090940 --add --no-interaction --env=prod || true
        ;;
    3)
        echo "📋 Available migrations:"
        php bin/console doctrine:migrations:list --env=prod
        echo
        echo "Run migrations manually using:"
        echo "php bin/console doctrine:migrations:execute VersionXXXXXXXXXXXXXX --env=prod"
        exit 0
        ;;
    *)
        echo "❌ Invalid option"
        exit 1
        ;;
esac

# Validate the final state
echo
echo "✅ Validating final database state..."
php bin/console doctrine:schema:validate --env=prod

if [ $? -eq 0 ]; then
    echo "✅ Database schema validation passed!"
else
    echo "❌ Database schema validation failed!"
    exit 1
fi

# Show final migration status
echo
echo "📊 Final migration status:"
php bin/console doctrine:migrations:status --env=prod

echo
echo "🎉 Production migration deployment completed successfully!"
echo "========================================================"
echo
echo "📋 Summary:"
echo "- All tables created and properly configured"
echo "- Foreign key relationships established"
echo "- Indexes optimized for performance"
echo "- Migration tracking properly initialized"
echo
echo "🔗 Key tables deployed:"
echo "  ✅ users (with authentication and profile features)"
echo "  ✅ user_integrations (API credentials management)"
echo "  ✅ subscription_plans & user_subscriptions (billing)"
echo "  ✅ projects & designs (core design platform)"
echo "  ✅ layers (design composition)"
echo "  ✅ media (asset management)"
echo "  ✅ templates (design templates)"
echo "  ✅ plugins (extensibility)"
echo "  ✅ export_jobs (async export processing)"
echo "  ✅ video_analysis (AI-powered features)"
echo "  ✅ shapes (design assets)"
echo
echo "🚀 Your production database is ready!"
