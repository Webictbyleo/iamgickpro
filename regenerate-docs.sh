#!/bin/bash

# Script to regenerate API documentation
# Usage: ./regenerate-docs.sh

echo "🚀 Regenerating API Documentation..."
echo "📁 Working directory: $(pwd)"

# Change to backend directory
cd backend || { echo "❌ Error: backend directory not found"; exit 1; }

# Generate new documentation
echo "📝 Running documentation generator..."
php -d output_buffering=0 scripts/generate-api-docs.php

# Check if generation was successful
if [ $? -eq 0 ]; then
    echo "✅ API Documentation regenerated successfully!"
    echo "📄 File location: $(pwd)/../API_DOCUMENTATION.md"
    echo "📊 Documentation stats:"
    wc -l ../API_DOCUMENTATION.md
else
    echo "❌ Error: Documentation generation failed"
    exit 1
fi
