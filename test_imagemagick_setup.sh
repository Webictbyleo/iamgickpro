#!/bin/bash

echo "=== ImageMagick Installation and Configuration Test ==="
echo

# Check if ImageMagick is installed
echo "1. Checking ImageMagick installation..."
if command -v convert &> /dev/null; then
    echo "✓ ImageMagick convert command found"
    convert -version | head -3
else
    echo "✗ ImageMagick convert command not found"
fi

if command -v magick &> /dev/null; then
    echo "✓ ImageMagick 7 magick command found"
    magick -version | head -3
else
    echo "✗ ImageMagick 7 magick command not found"
fi

echo

# Check if Inkscape is installed
echo "2. Checking Inkscape installation..."
if command -v inkscape &> /dev/null; then
    echo "✓ Inkscape found"
    inkscape --version
else
    echo "✗ Inkscape not found"
fi

echo

# Check supported formats
echo "3. Checking supported image formats..."
if command -v convert &> /dev/null; then
    echo "Supported formats:"
    convert -list format | grep -E "(JPEG|PNG|WEBP|SVG|GIF)" | head -10
fi

echo

# Test basic ImageMagick operations
echo "4. Testing basic ImageMagick operations..."

# Create test directories
mkdir -p /tmp/imagemagick-test
cd /tmp/imagemagick-test

# Create a simple test image
if command -v convert &> /dev/null; then
    echo "Creating test image..."
    convert -size 100x100 xc:red test_red.png
    if [ -f test_red.png ]; then
        echo "✓ Basic image creation successful"
        
        # Test format conversion
        convert test_red.png test_red.jpg
        if [ -f test_red.jpg ]; then
            echo "✓ Format conversion (PNG to JPEG) successful"
        else
            echo "✗ Format conversion failed"
        fi
        
        # Test resizing
        convert test_red.png -resize 50x50 test_red_small.png
        if [ -f test_red_small.png ]; then
            echo "✓ Image resizing successful"
        else
            echo "✗ Image resizing failed"
        fi
        
    else
        echo "✗ Basic image creation failed"
    fi
fi

echo

# Test SVG processing if Inkscape is available
echo "5. Testing SVG processing..."
if command -v inkscape &> /dev/null; then
    # Create a simple SVG
    cat > test.svg << 'EOF'
<?xml version="1.0" encoding="UTF-8"?>
<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
  <rect width="100" height="100" fill="blue"/>
  <circle cx="50" cy="50" r="20" fill="yellow"/>
</svg>
EOF
    
    if [ -f test.svg ]; then
        echo "✓ Test SVG created"
        
        # Test SVG to PNG conversion with Inkscape
        inkscape --export-type=png --export-filename=test_inkscape.png test.svg 2>/dev/null
        if [ -f test_inkscape.png ]; then
            echo "✓ SVG to PNG conversion with Inkscape successful"
        else
            echo "✗ SVG to PNG conversion with Inkscape failed"
        fi
        
        # Test SVG to PNG conversion with ImageMagick
        if command -v convert &> /dev/null; then
            convert test.svg test_imagemagick.png 2>/dev/null
            if [ -f test_imagemagick.png ]; then
                echo "✓ SVG to PNG conversion with ImageMagick successful"
            else
                echo "✗ SVG to PNG conversion with ImageMagick failed"
            fi
        fi
    fi
fi

echo

# Check directory permissions
echo "6. Checking directory permissions..."
BACKEND_DIR="/var/www/html/iamgickpro/backend"
UPLOAD_DIR="$BACKEND_DIR/public/uploads"
EXPORT_DIR="$BACKEND_DIR/var/exports"

echo "Checking upload directories..."
for dir in "$UPLOAD_DIR/media" "$UPLOAD_DIR/thumbnails" "$EXPORT_DIR" "$EXPORT_DIR/processed"; do
    if [ -d "$dir" ]; then
        echo "✓ Directory exists: $dir"
        if [ -w "$dir" ]; then
            echo "  ✓ Directory is writable"
        else
            echo "  ✗ Directory is not writable"
        fi
    else
        echo "✗ Directory does not exist: $dir"
        echo "  Creating directory..."
        mkdir -p "$dir" 2>/dev/null
        if [ -d "$dir" ]; then
            echo "  ✓ Directory created successfully"
        else
            echo "  ✗ Failed to create directory"
        fi
    fi
done

echo

# Test PHP ImageMagick extension
echo "7. Checking PHP ImageMagick extensions..."
cd "$BACKEND_DIR"
php -m | grep -i imagick && echo "✓ PHP Imagick extension found" || echo "✗ PHP Imagick extension not found"
php -m | grep -i gd && echo "✓ PHP GD extension found" || echo "✗ PHP GD extension not found"

echo

# Clean up
rm -rf /tmp/imagemagick-test

echo "=== Test Complete ==="
echo
echo "Next steps:"
echo "1. If ImageMagick is not installed, run: sudo apt-get install imagemagick"
echo "2. If Inkscape is not installed, run: sudo apt-get install inkscape"
echo "3. If PHP extensions are missing, run: sudo apt-get install php-imagick php-gd"
echo "4. Ensure all upload directories have proper write permissions"
echo "5. Update .env file with correct ImageMagick paths"
