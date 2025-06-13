#!/bin/bash

# Media Serving Test Script
# Tests media file serving in both development and production environments

set -e

echo "🧪 Testing Media Serving Functionality"
echo "======================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
DEV_SERVER_URL="http://localhost:8000"
DEV_SERVER_PID=""

# Function to start dev server if not running
start_dev_server() {
    echo -e "${YELLOW}Starting PHP development server...${NC}"
    cd /var/www/html/iamgickpro/backend
    php -S localhost:8000 -t public/ > /dev/null 2>&1 &
    DEV_SERVER_PID=$!
    sleep 3
    echo -e "${GREEN}Development server started (PID: $DEV_SERVER_PID)${NC}"
}

# Function to stop dev server
stop_dev_server() {
    if [ ! -z "$DEV_SERVER_PID" ]; then
        echo -e "${YELLOW}Stopping development server...${NC}"
        kill $DEV_SERVER_PID 2>/dev/null || true
        wait $DEV_SERVER_PID 2>/dev/null || true
        echo -e "${GREEN}Development server stopped${NC}"
    fi
}

# Function to test URL
test_url() {
    local url=$1
    local description=$2
    
    echo -n "Testing $description... "
    
    if curl -s -o /dev/null -w "%{http_code}" "$url" | grep -q "200\|304"; then
        echo -e "${GREEN}✓ PASS${NC}"
        return 0
    else
        echo -e "${RED}✗ FAIL${NC}"
        return 1
    fi
}

# Function to test with headers
test_url_with_headers() {
    local url=$1
    local description=$2
    
    echo "Testing $description:"
    echo "URL: $url"
    
    response=$(curl -s -I "$url")
    status_code=$(echo "$response" | head -n1 | cut -d' ' -f2)
    
    if [[ "$status_code" == "200" ]]; then
        echo -e "${GREEN}✓ Status: $status_code${NC}"
        
        # Check for security headers
        echo "$response" | grep -i "content-type" && echo -e "${GREEN}✓ Content-Type header present${NC}"
        echo "$response" | grep -i "cache-control" && echo -e "${GREEN}✓ Cache-Control header present${NC}"
        echo "$response" | grep -i "x-content-type-options" && echo -e "${GREEN}✓ Security headers present${NC}"
        
        echo ""
        return 0
    else
        echo -e "${RED}✗ Status: $status_code${NC}"
        echo ""
        return 1
    fi
}

# Cleanup function
cleanup() {
    stop_dev_server
    exit
}

# Set up trap for cleanup
trap cleanup EXIT INT TERM

# Create test files if they don't exist
echo -e "${YELLOW}Creating test media files...${NC}"
cd /var/www/html/iamgickpro/backend

# Create media directory if it doesn't exist
mkdir -p public/uploads/media
mkdir -p public/uploads/thumbnails

# Create test image
if [ ! -f "public/uploads/media/test.jpg" ]; then
    php -r "
    \$img = imagecreate(200, 100);
    \$bg = imagecolorallocate(\$img, 70, 130, 180);
    \$text_color = imagecolorallocate(\$img, 255, 255, 255);
    imagestring(\$img, 5, 50, 40, 'TEST MEDIA', \$text_color);
    imagejpeg(\$img, 'public/uploads/media/test.jpg', 90);
    imagedestroy(\$img);
    echo 'Created test.jpg\n';
    "
fi

# Create test thumbnail
if [ ! -f "public/uploads/thumbnails/thumb_test.jpg" ]; then
    php -r "
    \$img = imagecreate(100, 100);
    \$bg = imagecolorallocate(\$img, 34, 139, 34);
    \$text_color = imagecolorallocate(\$img, 255, 255, 255);
    imagestring(\$img, 3, 25, 45, 'THUMB', \$text_color);
    imagejpeg(\$img, 'public/uploads/thumbnails/thumb_test.jpg', 90);
    imagedestroy(\$img);
    echo 'Created thumb_test.jpg\n';
    "
fi

echo -e "${GREEN}Test files created successfully!${NC}"

# Check if dev server is already running
if ! curl -s "$DEV_SERVER_URL" > /dev/null 2>&1; then
    start_dev_server
else
    echo -e "${GREEN}Development server already running${NC}"
fi

echo ""
echo -e "${YELLOW}Testing Media File Serving${NC}"
echo "-------------------------"

# Test direct media file access
test_url_with_headers "$DEV_SERVER_URL/media/test.jpg" "Direct Media File"

# Test thumbnail access
test_url_with_headers "$DEV_SERVER_URL/thumbnails/thumb_test.jpg" "Thumbnail File"

# Test non-existent file (should return 404)
echo "Testing non-existent file (should return 404):"
response_code=$(curl -s -o /dev/null -w "%{http_code}" "$DEV_SERVER_URL/media/nonexistent.jpg")
if [[ "$response_code" == "404" ]]; then
    echo -e "${GREEN}✓ Non-existent file returns 404 correctly${NC}"
else
    echo -e "${RED}✗ Non-existent file returned: $response_code${NC}"
fi

echo ""
echo -e "${YELLOW}Testing Symfony Routes${NC}"
echo "---------------------"

# Test Symfony media routes
test_url "$DEV_SERVER_URL/api/media" "Media API endpoint"

echo ""
echo -e "${YELLOW}Performance Test${NC}"
echo "---------------"

# Simple performance test
echo "Testing media file download speed..."
time_result=$(curl -s -w "%{time_total}" -o /dev/null "$DEV_SERVER_URL/media/test.jpg")
echo "Download time: ${time_result}s"

echo ""
echo -e "${GREEN}✅ Media serving tests completed!${NC}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. For production, deploy using the nginx-production.conf"
echo "2. Ensure PHP-FPM is configured correctly"
echo "3. Test with real media files and different formats"
echo "4. Monitor performance and adjust caching headers as needed"
echo ""
echo -e "${YELLOW}Production URLs will be:${NC}"
echo "- https://your-domain.com/media/filename.jpg"
echo "- https://your-domain.com/thumbnails/thumb_filename.jpg"
echo "- https://your-domain.com/secure-media/uuid (authenticated)"
