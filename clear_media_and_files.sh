#!/bin/bash

# Media Cleanup Script
# Clears all media records from database and removes uploaded files

set -e

echo "🧹 Media Table and Files Cleanup Script"
echo "======================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BACKEND_DIR="/var/www/html/iamgickpro/backend"
UPLOADS_DIR="$BACKEND_DIR/public/uploads"

echo -e "${YELLOW}This script will:${NC}"
echo "1. Clear all records from the media table"
echo "2. Remove all uploaded media files"
echo "3. Remove all generated thumbnails"
echo "4. Remove all avatar files"
echo "5. Remove all plugin files"
echo ""

# Safety confirmation
read -p "Are you sure you want to proceed? This action cannot be undone! (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo -e "${RED}Operation cancelled.${NC}"
    exit 0
fi

echo ""
echo -e "${BLUE}Starting cleanup process...${NC}"

# Step 1: Clear media table
echo -e "${YELLOW}Step 1: Clearing media table...${NC}"
cd "$BACKEND_DIR"

# Create SQL script to clear media table
cat > clear_media.sql << EOF
-- Clear media table
TRUNCATE TABLE media;

-- Reset auto-increment if needed
ALTER TABLE media AUTO_INCREMENT = 1;

-- Show result
SELECT COUNT(*) as remaining_records FROM media;
EOF

# Execute SQL script
if command -v mysql &> /dev/null; then
    echo "Executing SQL commands via mysql..."
    # Try to execute with Symfony's database URL
    DB_URL=$(php bin/console debug:config doctrine | grep -A 1 "database_url" | tail -1 | sed 's/.*: //' | tr -d '"')
    
    if [[ $DB_URL =~ mysql://([^:]+):([^@]+)@([^:]+):([0-9]+)/(.+) ]]; then
        DB_USER="${BASH_REMATCH[1]}"
        DB_PASS="${BASH_REMATCH[2]}"
        DB_HOST="${BASH_REMATCH[3]}"
        DB_PORT="${BASH_REMATCH[4]}"
        DB_NAME="${BASH_REMATCH[5]}"
        
        mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < clear_media.sql
        echo -e "${GREEN}✓ Media table cleared successfully${NC}"
    else
        echo -e "${RED}Could not parse database URL. Trying Symfony console...${NC}"
        php bin/console doctrine:query:sql "TRUNCATE TABLE media"
        echo -e "${GREEN}✓ Media table cleared via Symfony console${NC}"
    fi
else
    echo "MySQL client not found. Using Symfony console..."
    php bin/console doctrine:query:sql "TRUNCATE TABLE media"
    echo -e "${GREEN}✓ Media table cleared via Symfony console${NC}"
fi

# Clean up SQL file
rm -f clear_media.sql

# Step 2: Remove uploaded files
echo -e "${YELLOW}Step 2: Removing uploaded files...${NC}"

if [ -d "$UPLOADS_DIR" ]; then
    # Count files before deletion
    MEDIA_COUNT=$(find "$UPLOADS_DIR/media" -type f 2>/dev/null | wc -l || echo "0")
    THUMBNAIL_COUNT=$(find "$UPLOADS_DIR/thumbnails" -type f 2>/dev/null | wc -l || echo "0")
    AVATAR_COUNT=$(find "$UPLOADS_DIR/avatars" -type f 2>/dev/null | wc -l || echo "0")
    PLUGIN_COUNT=$(find "$UPLOADS_DIR/plugins" -type f 2>/dev/null | wc -l || echo "0")
    
    echo "Files to be removed:"
    echo "  - Media files: $MEDIA_COUNT"
    echo "  - Thumbnails: $THUMBNAIL_COUNT"
    echo "  - Avatars: $AVATAR_COUNT"
    echo "  - Plugin files: $PLUGIN_COUNT"
    echo ""
    
    # Remove media files
    if [ -d "$UPLOADS_DIR/media" ]; then
        find "$UPLOADS_DIR/media" -type f -delete 2>/dev/null || true
        find "$UPLOADS_DIR/media" -type d -empty -delete 2>/dev/null || true
        echo -e "${GREEN}✓ Media files removed${NC}"
    fi
    
    # Remove thumbnails
    if [ -d "$UPLOADS_DIR/thumbnails" ]; then
        find "$UPLOADS_DIR/thumbnails" -type f -delete 2>/dev/null || true
        find "$UPLOADS_DIR/thumbnails" -type d -empty -delete 2>/dev/null || true
        echo -e "${GREEN}✓ Thumbnail files removed${NC}"
    fi
    
    # Remove avatars
    if [ -d "$UPLOADS_DIR/avatars" ]; then
        find "$UPLOADS_DIR/avatars" -type f -delete 2>/dev/null || true
        find "$UPLOADS_DIR/avatars" -type d -empty -delete 2>/dev/null || true
        echo -e "${GREEN}✓ Avatar files removed${NC}"
    fi
    
    # Remove plugin files
    if [ -d "$UPLOADS_DIR/plugins" ]; then
        find "$UPLOADS_DIR/plugins" -type f -delete 2>/dev/null || true
        find "$UPLOADS_DIR/plugins" -type d -empty -delete 2>/dev/null || true
        echo -e "${GREEN}✓ Plugin files removed${NC}"
    fi
    
    # Recreate directory structure
    mkdir -p "$UPLOADS_DIR/media"
    mkdir -p "$UPLOADS_DIR/thumbnails"
    mkdir -p "$UPLOADS_DIR/avatars"
    mkdir -p "$UPLOADS_DIR/plugins"
    
    echo -e "${GREEN}✓ Upload directory structure recreated${NC}"
else
    echo -e "${YELLOW}Uploads directory not found, creating structure...${NC}"
    mkdir -p "$UPLOADS_DIR/media"
    mkdir -p "$UPLOADS_DIR/thumbnails"
    mkdir -p "$UPLOADS_DIR/avatars"
    mkdir -p "$UPLOADS_DIR/plugins"
    echo -e "${GREEN}✓ Upload directory structure created${NC}"
fi

# Step 3: Clear Symfony cache
echo -e "${YELLOW}Step 3: Clearing Symfony cache...${NC}"
php bin/console cache:clear
echo -e "${GREEN}✓ Symfony cache cleared${NC}"

# Step 4: Verification
echo -e "${YELLOW}Step 4: Verifying cleanup...${NC}"

# Check database
REMAINING_RECORDS=$(php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM media" | grep -o '[0-9]*' | head -1 || echo "0")
echo "Database records remaining: $REMAINING_RECORDS"

# Check files
REMAINING_MEDIA=$(find "$UPLOADS_DIR/media" -type f 2>/dev/null | wc -l || echo "0")
REMAINING_THUMBNAILS=$(find "$UPLOADS_DIR/thumbnails" -type f 2>/dev/null | wc -l || echo "0")
REMAINING_AVATARS=$(find "$UPLOADS_DIR/avatars" -type f 2>/dev/null | wc -l || echo "0")
REMAINING_PLUGINS=$(find "$UPLOADS_DIR/plugins" -type f 2>/dev/null | wc -l || echo "0")

echo "Files remaining:"
echo "  - Media files: $REMAINING_MEDIA"
echo "  - Thumbnails: $REMAINING_THUMBNAILS"  
echo "  - Avatars: $REMAINING_AVATARS"
echo "  - Plugin files: $REMAINING_PLUGINS"

# Final status
echo ""
if [ "$REMAINING_RECORDS" -eq 0 ] && [ "$REMAINING_MEDIA" -eq 0 ] && [ "$REMAINING_THUMBNAILS" -eq 0 ]; then
    echo -e "${GREEN}🎉 Cleanup completed successfully!${NC}"
    echo -e "${GREEN}All media records and files have been removed.${NC}"
else
    echo -e "${YELLOW}⚠️  Cleanup completed with some remaining items.${NC}"
    echo -e "${YELLOW}This may be normal if there are system files or other data.${NC}"
fi

echo ""
echo -e "${BLUE}Cleanup Summary:${NC}"
echo "- Media table: Cleared"
echo "- Upload directories: Cleaned and recreated"
echo "- Symfony cache: Cleared"
echo "- Directory structure: Ready for new uploads"

echo ""
echo -e "${GREEN}You can now start uploading media files again!${NC}"
