#!/bin/bash

# Test Media Serving with Vite Proxy
# This script tests the media serving functionality through Vite's development proxy

set -e

echo "🧪 Testing Media Serving with Vite Proxy"
echo "========================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
FRONTEND_URL="http://localhost:3000"
BACKEND_URL="http://localhost:8000"

# Function to check if a service is running
check_service() {
    local url=$1
    local name=$2
    
    echo -n "Checking $name... "
    if curl -s "$url" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Running${NC}"
        return 0
    else
        echo -e "${RED}✗ Not running${NC}"
        return 1
    fi
}

# Function to test URL with expected status
test_url() {
    local url=$1
    local description=$2
    local expected_status=${3:-200}
    
    echo -n "Testing $description... "
    
    status_code=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null || echo "000")
    
    if [[ "$status_code" == "$expected_status" ]]; then
        echo -e "${GREEN}✓ $status_code${NC}"
        return 0
    else
        echo -e "${RED}✗ $status_code (expected $expected_status)${NC}"
        return 1
    fi
}

# Function to test with detailed headers
test_url_detailed() {
    local url=$1
    local description=$2
    
    echo -e "${BLUE}Testing $description:${NC}"
    echo "URL: $url"
    
    response=$(curl -s -I "$url" 2>/dev/null || echo "Connection failed")
    
    if echo "$response" | grep -q "HTTP.*200"; then
        echo -e "${GREEN}✓ Status: 200 OK${NC}"
        
        # Check for expected headers
        echo "$response" | grep -i "content-type" | head -1
        echo "$response" | grep -i "cache-control" | head -1
        echo "$response" | grep -i "x-content-type-options" | head -1
        
        echo ""
        return 0
    else
        echo -e "${RED}✗ Request failed${NC}"
        echo "$response" | head -3
        echo ""
        return 1
    fi
}

echo -e "${YELLOW}Step 1: Checking Services${NC}"
echo "------------------------"

# Check if backend is running
if ! check_service "$BACKEND_URL" "Backend (PHP)"; then
    echo -e "${YELLOW}Starting backend server...${NC}"
    cd /var/www/html/iamgickpro/backend
    php -S localhost:8000 -t public/ > /dev/null 2>&1 &
    BACKEND_PID=$!
    sleep 3
    
    if check_service "$BACKEND_URL" "Backend (PHP)"; then
        echo -e "${GREEN}Backend started successfully${NC}"
    else
        echo -e "${RED}Failed to start backend${NC}"
        exit 1
    fi
fi

# Check if frontend is running
if ! check_service "$FRONTEND_URL" "Frontend (Vite)"; then
    echo -e "${YELLOW}Frontend not running. Please start it with:${NC}"
    echo "cd frontend && npm run dev"
    echo ""
    echo -e "${YELLOW}Then run this test again.${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 2: Testing Direct Backend Access${NC}"
echo "-----------------------------------"

# Test direct backend access
test_url "$BACKEND_URL/media/test.jpg" "Direct backend media"
test_url "$BACKEND_URL/thumbnails/thumb_test.jpg" "Direct backend thumbnail"

echo ""
echo -e "${YELLOW}Step 3: Testing Vite Proxy${NC}"
echo "-------------------------"

# Test through Vite proxy
test_url_detailed "$FRONTEND_URL/media/test.jpg" "Vite proxy media"
test_url_detailed "$FRONTEND_URL/thumbnails/thumb_test.jpg" "Vite proxy thumbnail"
test_url_detailed "$FRONTEND_URL/api/media" "Vite proxy API"

echo ""
echo -e "${YELLOW}Step 4: Testing Error Handling${NC}"
echo "-----------------------------"

# Test 404 handling
test_url "$FRONTEND_URL/media/nonexistent.jpg" "Non-existent media" 404

echo ""
echo -e "${YELLOW}Step 5: Testing CORS and Headers${NC}"
echo "---------------------------------"

# Test CORS headers
echo "Testing CORS headers..."
cors_response=$(curl -s -H "Origin: http://localhost:3000" -H "Access-Control-Request-Method: GET" -H "Access-Control-Request-Headers: X-Requested-With" -X OPTIONS "$FRONTEND_URL/api/media" 2>/dev/null || echo "Failed")

if echo "$cors_response" | grep -qi "access-control"; then
    echo -e "${GREEN}✓ CORS headers present${NC}"
else
    echo -e "${YELLOW}⚠ CORS headers not detected (may be handled by Vite)${NC}"
fi

echo ""
echo -e "${GREEN}✅ Vite Proxy Media Serving Test Complete!${NC}"
echo ""
echo -e "${BLUE}Summary:${NC}"
echo "- Media files are now served through Vite proxy"
echo "- Frontend URLs (localhost:3000) proxy to backend (localhost:8000)"  
echo "- No need to manage CORS in development"
echo "- Simplified development workflow"
echo ""
echo -e "${BLUE}URLs to test in browser:${NC}"
echo "- http://localhost:3000/media/test.jpg"
echo "- http://localhost:3000/thumbnails/thumb_test.jpg"
echo "- http://localhost:3000/api/media (API endpoint)"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Update your Vue components to use the new media utils"
echo "2. Test file uploads and media management"
echo "3. Verify that all API calls work through the proxy"

# Cleanup
if [ ! -z "$BACKEND_PID" ]; then
    echo ""
    echo -e "${YELLOW}Stopping test backend server...${NC}"
    kill $BACKEND_PID 2>/dev/null || true
fi
