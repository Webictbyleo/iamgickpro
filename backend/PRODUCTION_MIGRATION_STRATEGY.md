# Production Migration Strategy

## Problem Statement

The current development environment has:
- ✅ 16 migrations executed in the database
- ❌ Only 2 migration files available locally (14 missing)
- ⚠️ Risk: Cannot deploy to production without all migration files

## Solution: Comprehensive Migration Approach

### 1. Created Complete Schema Migration

**File**: `migrations/Version20250624000000.php`

This migration includes:
- **All 17 tables** with complete schema
- **All relationships** and foreign keys
- **All indexes** for performance
- **All constraints** for data integrity

### 2. Tables Included

| Table | Purpose | Key Features |
|-------|---------|--------------|
| `users` | User management | Authentication, profiles, preferences |
| `user_integrations` | **API credentials** | Encrypted storage for OpenAI, RemoveBG, etc. |
| `subscription_plans` | Billing plans | Monthly/yearly pricing, features |
| `user_subscriptions` | User billing | Active subscriptions, trial periods |
| `plan_features` | Plan capabilities | Feature flags per plan |
| `plan_limits` | Usage limits | Storage, exports, API calls |
| `projects` | Project organization | User projects with designs |
| `designs` | Core designs | Canvas data, animations |
| `layers` | Design composition | Layer hierarchy, transforms |
| `media` | Asset management | Images, videos, uploads |
| `templates` | Design templates | Reusable design templates |
| `plugins` | Extensibility | Third-party plugins |
| `export_jobs` | Async processing | Background export tasks |
| `video_analysis` | AI features | YouTube thumbnail analysis |
| `shapes` | Design assets | SVG shapes library |
| `messenger_messages` | Job queue | Symfony Messenger |

### 3. User Integrations Table Details

```sql
CREATE TABLE user_integrations (
    id INT AUTO_INCREMENT NOT NULL,
    service_name VARCHAR(50) NOT NULL,        -- openai, removebg, unsplash, pexels, replicate
    encrypted_credentials LONGTEXT NOT NULL, -- Encrypted API keys/tokens
    settings JSON DEFAULT NULL,              -- Service-specific settings
    is_active TINYINT(1) DEFAULT 1,         -- Enable/disable integration
    last_tested_at DATETIME DEFAULT NULL,    -- Last connection test
    is_connection_valid TINYINT(1) DEFAULT 0, -- Connection status
    last_error VARCHAR(255) DEFAULT NULL,    -- Last error message
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    user_id INT NOT NULL,
    UNIQUE INDEX user_service_unique (user_id, service_name), -- One integration per service per user
    FOREIGN KEY (user_id) REFERENCES users (id)
);
```

## Deployment Options

### Option 1: Fresh Production Deployment (Recommended)

**When to use**: New production environment

```bash
cd backend
./scripts/production-migration.sh
# Choose option 1
```

**What it does**:
1. Creates database if needed
2. Runs all available migrations
3. Validates schema
4. Sets up tracking

### Option 2: Existing Database Update

**When to use**: Existing production database that already has tables

```bash
cd backend
./scripts/production-migration.sh
# Choose option 2
```

**What it does**:
1. Marks comprehensive migration as executed (without running)
2. Updates migration tracking
3. Validates existing schema

### Option 3: Manual Migration Control

**When to use**: Custom deployment scenarios

```bash
cd backend
# Check what migrations are available
php bin/console doctrine:migrations:list --env=prod

# Execute specific migration
php bin/console doctrine:migrations:execute Version20250624000000 --env=prod

# Mark as executed without running
php bin/console doctrine:migrations:version Version20250624000000 --add --env=prod
```

## Verification Steps

After deployment, verify:

```bash
# 1. Check migration status
php bin/console doctrine:migrations:status --env=prod

# 2. Validate schema
php bin/console doctrine:schema:validate --env=prod

# 3. List tables
php bin/console doctrine:query:sql "SHOW TABLES" --env=prod

# 4. Test user integrations table
php bin/console doctrine:query:sql "DESCRIBE user_integrations" --env=prod
```

## Testing User Integrations

After deployment, test the integration system:

```php
// Create a test integration
$user = $userRepository->findOneBy(['email' => 'test@example.com']);
$integration = new UserIntegration();
$integration->setUser($user);
$integration->setServiceName('openai');
$integration->setEncryptedCredentials('encrypted_api_key_here');
$integration->setSettings(['model' => 'gpt-4']);

$entityManager->persist($integration);
$entityManager->flush();
```

## Rollback Strategy

If needed, rollback using:

```bash
# Rollback specific migration
php bin/console doctrine:migrations:execute Version20250624000000 --down --env=prod

# Or drop all tables (nuclear option)
php bin/console doctrine:schema:drop --force --env=prod
```

## Security Considerations

1. **Encrypted Credentials**: The `user_integrations.encrypted_credentials` field stores sensitive API keys
2. **Unique Constraints**: Prevents duplicate integrations per user/service
3. **Foreign Keys**: Ensures data integrity
4. **Indexes**: Optimized for performance

## Future Migration Strategy

For future schema changes:

1. **Always create migration files** using `doctrine:migrations:diff`
2. **Test migrations** in development first
3. **Keep migration history** - never delete migration files
4. **Use version control** for migration files
5. **Document breaking changes** in migration descriptions

## Files Created

1. `migrations/Version20250624000000.php` - Complete schema migration
2. `scripts/production-migration.sh` - Deployment script
3. `PRODUCTION_MIGRATION_STRATEGY.md` - This documentation

## Next Steps

1. ✅ Test the migration in a staging environment
2. ✅ Run the production deployment script
3. ✅ Verify all tables exist and relationships work
4. ✅ Test user integration functionality
5. ✅ Monitor for any issues post-deployment

This strategy ensures your production environment will have a complete, working database schema including the user integrations system for managing API credentials securely.
