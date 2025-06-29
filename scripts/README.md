# Template Import Scripts

This directory contains automation scripts for importing design templates from the [design-templates GitHub repository](https://github.com/Webictbyleo/design-templates) into the IAMGickPro platform.

## Features

- 🚀 **Automated Import**: Download and import 666+ design templates
- 🖼️ **Thumbnail Generation**: Generate high-quality thumbnails using the actual DesignRenderer
- 📦 **Asset Management**: Download and organize template assets
- 🏷️ **Smart Categorization**: Automatically categorize templates based on dimensions and content
- 🔄 **Batch Processing**: Process templates in batches with progress tracking
- 🛡️ **Error Handling**: Robust error handling with detailed reporting
- 🔍 **Dry Run Mode**: Preview what would be imported without making changes

## Quick Start

### 1. Install Dependencies

```bash
cd scripts
npm install
```

### 2. Ensure Backend is Running

Make sure the backend server is running on `http://localhost:8000`:

```bash
cd ../backend
php -S localhost:8000 -t public/
```

### 3. Test the Import

```bash
# Test with a single template (dry run)
./import-templates.js test

# Preview 5 templates
./import-templates.js sample
```

### 4. Import Templates

```bash
# Import social media templates
./import-templates.js social

# Import presentation templates  
./import-templates.js presentation

# Import all templates (use with caution)
./import-templates.js all --dry-run  # Preview first
./import-templates.js all            # Actually import
```

## Available Scripts

### CLI Tool (Recommended)
```bash
./import-templates.js <command> [options]
```

**Commands:**
- `test` - Test import with 1 template (dry run)
- `sample` - Import 5 templates (dry run preview)
- `social` - Import social media templates (10 max)
- `presentation` - Import presentation templates (10 max)
- `all` - Import all templates
- `force-all` - Force re-import all templates

### Direct Script Usage
```bash
# Advanced template importer (recommended)
node advanced-template-importer.js [options]

# Basic template importer (fallback)
node load-templates.js [options]
```

## Options

- `--limit <number>` - Limit number of templates to process
- `--force` - Force re-import of existing templates
- `--dry-run` - Show what would be imported without doing it
- `--category <name>` - Filter by category (social-media, presentation, print, etc.)
- `--no-previews` - Skip generating preview images

## Examples

```bash
# Test the system with 1 template
./import-templates.js test

# Preview what would be imported for social media
./import-templates.js social --dry-run

# Import first 10 templates from any category
node advanced-template-importer.js --limit 10

# Force re-import social media templates
./import-templates.js social --force

# Import all presentation templates
node advanced-template-importer.js --category presentation
```

## How It Works

### 1. Template Discovery
- Fetches the `designs_manifest.json` from the GitHub repository
- Filters templates based on category, limit, and other criteria

### 2. Asset Processing
- Downloads template JSON data from `exported_designs/` folder
- Downloads associated assets from `converted_assets/` folder
- Stores assets in `backend/public/uploads/templates/`

### 3. Thumbnail Generation
- Uses the actual DesignRenderer from the frontend for consistency
- Generates thumbnails (400x300) and previews (800x600)
- Stores thumbnails in `backend/public/uploads/thumbnails/`

### 4. Database Import
- Makes API calls to the backend to create template records
- Includes proper categorization, tagging, and metadata

### 5. Quality Assurance
- Validates template data structure
- Handles missing assets gracefully
- Provides detailed error reporting and success statistics

## Template Categories

Templates are automatically categorized based on dimensions and title:

- **social-media**: Instagram, Facebook posts and stories
- **presentation**: HD presentation slides
- **print**: Flyers, posters, business cards
- **logo**: Logo and branding designs
- **web-graphics**: Web banners and graphics
- **marketing**: General marketing materials

## Directory Structure

```
scripts/
├── package.json                    # Dependencies
├── import-templates.js             # CLI tool (main entry point)
├── advanced-template-importer.js   # Advanced importer with DesignRenderer
├── load-templates.js               # Basic importer (fallback)
└── README.md                       # This file
```

## Dependencies

- **jsdom**: DOM environment for running frontend code in Node.js
- **canvas**: HTML5 Canvas implementation for thumbnail generation
- **puppeteer**: Headless browser for complex rendering (fallback option)

## Troubleshooting

### Common Issues

1. **Backend not running**
   ```
   Error: ECONNREFUSED connecting to localhost:8000
   ```
   Solution: Start the backend server with `php -S localhost:8000 -t public/`

2. **Permission errors**
   ```
   Error: EACCES permission denied
   ```
   Solution: Ensure upload directories are writable:
   ```bash
   chmod -R 755 ../backend/public/uploads/
   ```

3. **Canvas dependencies missing**
   ```
   Error: Canvas module not found
   ```
   Solution: Install canvas dependencies for your system:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install build-essential libcairo2-dev libpango1.0-dev libjpeg-dev libgif-dev librsvg2-dev
   
   # macOS
   brew install pkg-config cairo pango libpng jpeg giflib librsvg
   ```

4. **Memory issues with large imports**
   ```
   Error: JavaScript heap out of memory
   ```
   Solution: Increase Node.js memory limit:
   ```bash
   node --max-old-space-size=4096 advanced-template-importer.js
   ```

### Debug Mode

Run with verbose output:
```bash
DEBUG=* node advanced-template-importer.js --limit 1
```

### Checking Results

After import, verify in the application:
1. Open the frontend at `http://localhost:3000`
2. Navigate to Dashboard or Designs view
3. Check that templates appear in the template grid
4. Verify thumbnails are displayed correctly

## Contributing

1. **Adding New Importers**: Create new scripts following the pattern in `advanced-template-importer.js`
2. **Improving Categorization**: Update the `getTemplateCategory()` method
3. **Adding Formats**: Extend the thumbnail generation to support more formats
4. **Performance**: Optimize batch processing and memory usage

## API Integration

The scripts integrate with these backend endpoints:
- `GET /api/templates/{id}` - Check if template exists
- `POST /api/templates` - Create new template
- `PUT /api/templates/{id}` - Update existing template

## Performance Notes

- **Batch Size**: Templates are processed in batches of 5 to prevent overwhelming the system
- **Memory Management**: Large template data is processed incrementally
- **Asset Caching**: Assets are only downloaded once and reused
- **Thumbnail Optimization**: Images are optimized for web display

## License

These scripts are part of the IAMGickPro project and follow the same license terms.
