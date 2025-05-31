#!/bin/bash

echo "Testing CORS preflight request..."

# Test OPTIONS request (preflight)
echo "1. Testing OPTIONS preflight request:"
curl -X OPTIONS \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type,Authorization" \
  -v \
  http://localhost:8000/api/auth/login

echo -e "\n\n2. Testing actual POST request:"
curl -X POST \
  -H "Origin: http://localhost:5173" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test" \
  -d '{"email":"test@example.com","password":"password"}' \
  -v \
  http://localhost:8000/api/auth/login
