#!/usr/bin/env node

/**
 * Template Import Script
 * 
 * This script automates the process of:
 * 1. Fetching design templates from the GitHub repository
 * 2. Generating preview thumbnails using the DesignRenderer
 * 3. Importing templates into the database
 * 4. Managing template assets
 * 
 * Usage:
 *   node scripts/load-templates.js [options]
 * 
 * Options:
 *   --limit <number>    Limit the number of templates to process (default: all)
 *   --force            Force re-import of existing templates
 *   --dry-run          Show what would be imported without actually doing it
 *   --category <name>   Only import templates from specific category
 */

const https = require('https');
const fs = require('fs').promises;
const path = require('path');
const { execSync } = require('child_process');

// Configuration
const GITHUB_REPO = 'https://raw.githubusercontent.com/Webictbyleo/design-templates/main';
const EXPORTED_DESIGNS_URL = `${GITHUB_REPO}/exported_designs`;
const CONVERTED_ASSETS_URL = `${GITHUB_REPO}/converted_assets`;
const MANIFEST_URL = `${EXPORTED_DESIGNS_URL}/designs_manifest.json`;

// Backend API configuration
const BACKEND_URL = 'http://localhost:8000/api';
const UPLOAD_DIR = '../backend/public/uploads/templates';
const THUMBNAIL_DIR = '../backend/public/uploads/thumbnails';

class TemplateImporter {
    constructor(options = {}) {
        this.options = {
            limit: options.limit || null,
            force: options.force || false,
            dryRun: options.dryRun || false,
            category: options.category || null,
            ...options
        };
        
        this.stats = {
            processed: 0,
            imported: 0,
            skipped: 0,
            errors: 0
        };
    }

    /**
     * Main import process
     */
    async run() {
        console.log('🚀 Starting template import process...');
        console.log(`Options: ${JSON.stringify(this.options, null, 2)}`);

        try {
            // Ensure directories exist
            await this.ensureDirectories();

            // Fetch the manifest
            console.log('📋 Fetching template manifest...');
            const manifest = await this.fetchJSON(MANIFEST_URL);
            console.log(`Found ${manifest.totalDesigns} templates in repository`);

            // Filter templates based on options
            let templates = manifest.designs;
            if (this.options.category) {
                templates = templates.filter(t => this.getTemplateCategory(t) === this.options.category);
                console.log(`Filtered to ${templates.length} templates in category: ${this.options.category}`);
            }

            if (this.options.limit) {
                templates = templates.slice(0, this.options.limit);
                console.log(`Limited to ${templates.length} templates`);
            }

            // Process each template
            console.log(`\n🔄 Processing ${templates.length} templates...`);
            for (const templateMeta of templates) {
                await this.processTemplate(templateMeta);
            }

            // Print summary
            this.printSummary();

        } catch (error) {
            console.error('❌ Import process failed:', error.message);
            process.exit(1);
        }
    }

    /**
     * Process a single template
     */
    async processTemplate(templateMeta) {
        this.stats.processed++;
        const templateId = templateMeta.id;
        
        try {
            console.log(`\n[${this.stats.processed}] Processing template: ${templateMeta.name || templateId}`);

            // Check if template already exists (unless force mode)
            if (!this.options.force && await this.templateExists(templateId)) {
                console.log(`  ⏭️  Template already exists, skipping`);
                this.stats.skipped++;
                return;
            }

            if (this.options.dryRun) {
                console.log(`  🔍 [DRY RUN] Would import template: ${templateMeta.name}`);
                this.stats.imported++;
                return;
            }

            // Fetch full template data
            const templateUrl = `${EXPORTED_DESIGNS_URL}/${templateId}.json`;
            const templateData = await this.fetchJSON(templateUrl);

            // Generate thumbnail using headless browser
            const thumbnailPath = await this.generateThumbnail(templateData);

            // Download and process assets
            await this.downloadTemplateAssets(templateData);

            // Import into database
            await this.importToDatabase(templateData, thumbnailPath);

            console.log(`  ✅ Successfully imported template`);
            this.stats.imported++;

        } catch (error) {
            console.error(`  ❌ Failed to process template ${templateId}:`, error.message);
            this.stats.errors++;
        }
    }

    /**
     * Generate thumbnail for template using Puppeteer
     */
    async generateThumbnail(templateData) {
        console.log(`  🖼️  Generating thumbnail...`);

        // Create a temporary HTML file with the design renderer
        const htmlContent = this.createThumbnailHTML(templateData);
        const tempHtmlPath = path.join('/tmp', `template-${templateData.id}.html`);
        await fs.writeFile(tempHtmlPath, htmlContent);

        try {
            // Use Puppeteer to render thumbnail
            const thumbnailPath = path.join(THUMBNAIL_DIR, `${templateData.id}.png`);
            
            const puppeteerScript = `
                const puppeteer = require('puppeteer');
                const path = require('path');

                (async () => {
                    const browser = await puppeteer.launch({ headless: true });
                    const page = await browser.newPage();
                    
                    await page.setViewport({ width: 400, height: 300 });
                    await page.goto('file://${tempHtmlPath}', { waitUntil: 'networkidle0' });
                    
                    // Wait for the canvas to be rendered
                    await page.waitForSelector('#thumbnail-canvas', { timeout: 10000 });
                    
                    // Take screenshot of the canvas element
                    const canvas = await page.$('#thumbnail-canvas');
                    await canvas.screenshot({ path: '${thumbnailPath}' });
                    
                    await browser.close();
                    console.log('Thumbnail generated:', '${thumbnailPath}');
                })();
            `;

            // Execute puppeteer script
            const tempScriptPath = path.join('/tmp', `generate-thumb-${templateData.id}.js`);
            await fs.writeFile(tempScriptPath, puppeteerScript);
            
            execSync(`node ${tempScriptPath}`, { stdio: 'inherit' });
            
            // Clean up temporary files
            await fs.unlink(tempHtmlPath);
            await fs.unlink(tempScriptPath);

            return path.relative('../backend/public', thumbnailPath);

        } catch (error) {
            // Clean up on error
            try {
                await fs.unlink(tempHtmlPath);
            } catch {}
            throw new Error(`Thumbnail generation failed: ${error.message}`);
        }
    }

    /**
     * Create HTML content for thumbnail generation
     */
    createThumbnailHTML(templateData) {
        return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Template Thumbnail</title>
    <script src="https://unpkg.com/konva@9/konva.min.js"></script>
    <style>
        body { margin: 0; padding: 20px; background: #f5f5f5; }
        #container { width: 400px; height: 300px; background: white; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div id="container"></div>
    <canvas id="thumbnail-canvas" width="400" height="300" style="display: none;"></canvas>

    <script>
        // Template data
        const templateData = ${JSON.stringify(templateData)};
        
        // Initialize Konva stage
        const stage = new Konva.Stage({
            container: 'container',
            width: 400,
            height: 300
        });
        
        const layer = new Konva.Layer();
        stage.add(layer);
        
        // Calculate scale to fit template
        const scaleX = 400 / templateData.width;
        const scaleY = 300 / templateData.height;
        const scale = Math.min(scaleX, scaleY);
        
        // Add background
        const background = new Konva.Rect({
            x: 0,
            y: 0,
            width: 400,
            height: 300,
            fill: templateData.data?.backgroundColor || templateData.data?.background?.color || '#ffffff'
        });
        layer.add(background);
        
        // Render layers (simplified)
        if (templateData.layers) {
            templateData.layers.forEach((layerData, index) => {
                if (layerData.type === 'text') {
                    const text = new Konva.Text({
                        x: (layerData.x || 0) * scale,
                        y: (layerData.y || 0) * scale,
                        text: layerData.properties?.text || 'Sample Text',
                        fontSize: (layerData.properties?.fontSize || 16) * scale,
                        fill: layerData.properties?.color || '#000000',
                        fontFamily: layerData.properties?.fontFamily || 'Arial'
                    });
                    layer.add(text);
                } else if (layerData.type === 'shape') {
                    const shape = new Konva.Rect({
                        x: (layerData.x || 0) * scale,
                        y: (layerData.y || 0) * scale,
                        width: (layerData.width || 100) * scale,
                        height: (layerData.height || 100) * scale,
                        fill: layerData.properties?.fill || '#cccccc'
                    });
                    layer.add(shape);
                }
            });
        }
        
        layer.draw();
        
        // Export to canvas
        setTimeout(() => {
            const canvas = document.getElementById('thumbnail-canvas');
            const ctx = canvas.getContext('2d');
            
            // Copy stage content to canvas
            const stageCanvas = stage.toCanvas();
            ctx.drawImage(stageCanvas, 0, 0);
        }, 1000);
    </script>
</body>
</html>
        `;
    }

    /**
     * Download template assets
     */
    async downloadTemplateAssets(templateData) {
        console.log(`  📦 Processing assets...`);
        
        if (!templateData.layers) return;

        for (const layer of templateData.layers) {
            if (layer.type === 'image' && layer.properties?.src) {
                await this.downloadAsset(layer.properties.src);
            }
        }
    }

    /**
     * Download a single asset
     */
    async downloadAsset(assetPath) {
        if (!assetPath.startsWith('/converted_assets/')) return;

        const filename = path.basename(assetPath);
        const url = `${CONVERTED_ASSETS_URL}/${filename}`;
        const localPath = path.join(UPLOAD_DIR, filename);

        try {
            // Check if file already exists
            await fs.access(localPath);
            return; // File exists, skip download
        } catch {
            // File doesn't exist, download it
        }

        console.log(`    ⬇️  Downloading asset: ${filename}`);
        
        return new Promise((resolve, reject) => {
            const file = require('fs').createWriteStream(localPath);
            
            https.get(url, (response) => {
                if (response.statusCode !== 200) {
                    reject(new Error(`Failed to download ${url}: ${response.statusCode}`));
                    return;
                }
                
                response.pipe(file);
                file.on('finish', () => {
                    file.close();
                    resolve();
                });
                file.on('error', reject);
            }).on('error', reject);
        });
    }

    /**
     * Import template to database via API
     */
    async importToDatabase(templateData, thumbnailPath) {
        console.log(`  💾 Importing to database...`);

        const templatePayload = {
            uuid: templateData.id,
            name: templateData.name || templateData.title,
            description: templateData.description,
            category: this.getTemplateCategory(templateData),
            tags: this.getTemplateTags(templateData),
            width: templateData.width,
            height: templateData.height,
            canvas_settings: templateData.data || {},
            layers: templateData.layers || [],
            thumbnail_url: thumbnailPath,
            is_premium: this.isTemplatePremium(templateData),
            is_active: true,
            is_public: true
        };

        // Make API call to import template
        const response = await this.makeAPICall('POST', '/templates', templatePayload);
        
        if (response.status !== 'success') {
            throw new Error(`API import failed: ${response.message}`);
        }
    }

    /**
     * Determine template category from data
     */
    getTemplateCategory(templateData) {
        // Try to infer category from dimensions or title
        const { width, height } = templateData;
        const title = (templateData.title || templateData.name || '').toLowerCase();

        if (width === 1920 && height === 1080) return 'presentation';
        if (width === 1080 && height === 1080) return 'social-media';
        if (width === 1080 && height === 1920) return 'social-media';
        if (title.includes('instagram')) return 'social-media';
        if (title.includes('facebook')) return 'social-media';
        if (title.includes('presentation')) return 'presentation';
        if (title.includes('flyer')) return 'print';
        if (title.includes('logo')) return 'logo';

        // Default category
        return 'marketing';
    }

    /**
     * Extract tags from template data
     */
    getTemplateTags(templateData) {
        const tags = [];
        const title = (templateData.title || templateData.name || '').toLowerCase();

        // Add dimension-based tags
        const { width, height } = templateData;
        if (width === height) tags.push('square');
        else if (width > height) tags.push('landscape');
        else tags.push('portrait');

        // Add size tags
        if (width >= 1920) tags.push('hd');
        if (width === 1080) tags.push('instagram');

        // Add content tags based on title
        if (title.includes('business')) tags.push('business');
        if (title.includes('modern')) tags.push('modern');
        if (title.includes('minimal')) tags.push('minimal');
        if (title.includes('creative')) tags.push('creative');

        return tags.slice(0, 5); // Limit to 5 tags
    }

    /**
     * Determine if template should be premium
     */
    isTemplatePremium(templateData) {
        // For now, make 30% of templates premium
        return Math.random() < 0.3;
    }

    /**
     * Check if template already exists
     */
    async templateExists(templateId) {
        try {
            const response = await this.makeAPICall('GET', `/templates/${templateId}`);
            return response.status === 'success';
        } catch {
            return false;
        }
    }

    /**
     * Make API call to backend
     */
    async makeAPICall(method, endpoint, data = null) {
        return new Promise((resolve, reject) => {
            const url = `${BACKEND_URL}${endpoint}`;
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer YOUR_API_TOKEN' // TODO: Implement auth
                }
            };

            const req = require('http').request(url, options, (res) => {
                let body = '';
                res.on('data', (chunk) => body += chunk);
                res.on('end', () => {
                    try {
                        const response = JSON.parse(body);
                        resolve(response);
                    } catch (e) {
                        reject(new Error(`Invalid JSON response: ${body}`));
                    }
                });
            });

            req.on('error', reject);

            if (data) {
                req.write(JSON.stringify(data));
            }

            req.end();
        });
    }

    /**
     * Fetch JSON from URL
     */
    async fetchJSON(url) {
        return new Promise((resolve, reject) => {
            https.get(url, (res) => {
                let data = '';
                res.on('data', (chunk) => data += chunk);
                res.on('end', () => {
                    try {
                        resolve(JSON.parse(data));
                    } catch (e) {
                        reject(new Error(`Invalid JSON from ${url}: ${e.message}`));
                    }
                });
            }).on('error', reject);
        });
    }

    /**
     * Ensure required directories exist
     */
    async ensureDirectories() {
        const dirs = [UPLOAD_DIR, THUMBNAIL_DIR];
        
        for (const dir of dirs) {
            try {
                await fs.mkdir(dir, { recursive: true });
                console.log(`📁 Ensured directory exists: ${dir}`);
            } catch (error) {
                console.error(`Failed to create directory ${dir}:`, error.message);
            }
        }
    }

    /**
     * Print import summary
     */
    printSummary() {
        console.log('\n📊 Import Summary:');
        console.log('==================');
        console.log(`Processed: ${this.stats.processed}`);
        console.log(`Imported:  ${this.stats.imported}`);
        console.log(`Skipped:   ${this.stats.skipped}`);
        console.log(`Errors:    ${this.stats.errors}`);
        
        if (this.stats.errors > 0) {
            console.log('\n⚠️  Some templates failed to import. Check the error messages above.');
            process.exit(1);
        } else {
            console.log('\n✅ Template import completed successfully!');
        }
    }
}

// Parse command line arguments
function parseArgs() {
    const args = process.argv.slice(2);
    const options = {};

    for (let i = 0; i < args.length; i++) {
        const arg = args[i];
        
        switch (arg) {
            case '--limit':
                options.limit = parseInt(args[++i], 10);
                break;
            case '--force':
                options.force = true;
                break;
            case '--dry-run':
                options.dryRun = true;
                break;
            case '--category':
                options.category = args[++i];
                break;
            case '--help':
                console.log(`
Template Import Script

Usage: node scripts/load-templates.js [options]

Options:
  --limit <number>    Limit the number of templates to process
  --force            Force re-import of existing templates  
  --dry-run          Show what would be imported without doing it
  --category <name>   Only import templates from specific category
  --help             Show this help message

Examples:
  node scripts/load-templates.js --limit 10 --dry-run
  node scripts/load-templates.js --category social-media --force
                `);
                process.exit(0);
                break;
            default:
                console.error(`Unknown argument: ${arg}`);
                process.exit(1);
        }
    }

    return options;
}

// Main execution
if (require.main === module) {
    const options = parseArgs();
    const importer = new TemplateImporter(options);
    
    importer.run().catch((error) => {
        console.error('Fatal error:', error);
        process.exit(1);
    });
}

module.exports = { TemplateImporter };
