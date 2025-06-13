#!/bin/bash

# Test script to verify search endpoints are working correctly
# This script tests all search endpoints with proper authentication

echo "🔍 Testing Search Endpoints..."
echo "=================================="

# Backend URL
BACKEND_URL="http://localhost:8000"

# Test credentials (using the provided test account)
EMAIL="johndoe@example.com"
PASSWORD="Vyhd7Y#PjTb7!TA"

echo "📡 Step 1: Authenticating..."

# Login and get token
LOGIN_RESPONSE=$(curl -s -X POST "${BACKEND_URL}/api/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"${EMAIL}\",\"password\":\"${PASSWORD}\"}")

echo "Login response: $LOGIN_RESPONSE"

# Extract token using a more robust method
TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "❌ Failed to get authentication token"
    echo "Login response: $LOGIN_RESPONSE"
    exit 1
fi

echo "✅ Authentication successful"
echo "Token: ${TOKEN:0:20}..."

# Test Global Search
echo ""
echo "📖 Step 2: Testing Global Search..."
GLOBAL_SEARCH=$(curl -s -X GET "${BACKEND_URL}/api/search?q=test&limit=5" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json")

echo "Global Search Response:"
echo "$GLOBAL_SEARCH" | jq '.' 2>/dev/null || echo "$GLOBAL_SEARCH"

# Test Template Search
echo ""
echo "📄 Step 3: Testing Template Search..."
TEMPLATE_SEARCH=$(curl -s -X GET "${BACKEND_URL}/api/search/templates?q=test&limit=5" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json")

echo "Template Search Response:"
echo "$TEMPLATE_SEARCH" | jq '.' 2>/dev/null || echo "$TEMPLATE_SEARCH"

# Test Media Search
echo ""
echo "🖼️ Step 4: Testing Media Search..."
MEDIA_SEARCH=$(curl -s -X GET "${BACKEND_URL}/api/search/media?q=test&limit=5" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json")

echo "Media Search Response:"
echo "$MEDIA_SEARCH" | jq '.' 2>/dev/null || echo "$MEDIA_SEARCH"

# Test Project Search
echo ""
echo "📂 Step 5: Testing Project Search..."
PROJECT_SEARCH=$(curl -s -X GET "${BACKEND_URL}/api/search/projects?q=test&limit=5" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json")

echo "Project Search Response:"
echo "$PROJECT_SEARCH" | jq '.' 2>/dev/null || echo "$PROJECT_SEARCH"

# Test Search Suggestions
echo ""
echo "💡 Step 6: Testing Search Suggestions..."
SUGGESTIONS=$(curl -s -X GET "${BACKEND_URL}/api/search/suggestions?q=te&limit=5" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json")

echo "Search Suggestions Response:"
echo "$SUGGESTIONS" | jq '.' 2>/dev/null || echo "$SUGGESTIONS"

echo ""
echo "✅ Search endpoint testing completed!"
