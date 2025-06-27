#!/usr/bin/env node

/**
 * Template Browser
 * 
 * This script browses and analyzes available templates from the GitHub repository
 * without importing them. Useful for exploring what's available and planning imports.
 */

const https = require('https');

const CONFIG = {
    manifestUrl: 'https://raw.githubusercontent.com/Webictbyleo/design-templates/main/exported_designs/designs_manifest.json',
    repoUrl: 'https://raw.githubusercontent.com/Webictbyleo/design-templates/main'
};

class TemplateBrowser {
    constructor(options = {}) {
        this.options = {
            limit: options.limit || null,
            category: options.category || null,
            showDetails: options.showDetails || false,
            analyze: options.analyze || false,
            ...options
        };
    }

    async run() {
        console.log('🔍 Template Repository Browser');
        console.log('=============================\n');

        try {
            // Fetch manifest
            console.log('📋 Fetching template manifest...');
            const manifest = await this.fetchJSON(CONFIG.manifestUrl);
            console.log(`Found ${manifest.totalDesigns} templates\n`);

            // Filter templates
            let templates = this.filterTemplates(manifest.designs);

            if (this.options.analyze) {
                await this.analyzeTemplates(templates);
            } else {
                await this.browseTemplates(templates);
            }

        } catch (error) {
            console.error('❌ Browser failed:', error.message);
            process.exit(1);
        }
    }

    filterTemplates(templates) {
        let filtered = templates;

        if (this.options.category) {
            filtered = filtered.filter(t => {
                const category = this.getTemplateCategory(t);
                return category === this.options.category;
            });
            console.log(`📁 Filtered to ${filtered.length} templates in category: ${this.options.category}`);
        }

        if (this.options.limit) {
            filtered = filtered.slice(0, this.options.limit);
            console.log(`🔢 Limited to ${filtered.length} templates`);
        }

        return filtered;
    }

    async browseTemplates(templates) {
        console.log(`📚 Browsing ${templates.length} templates:\n`);

        for (let i = 0; i < templates.length; i++) {
            const template = templates[i];
            const num = (i + 1).toString().padStart(3, ' ');
            const name = (template.name || template.title || template.id).substring(0, 40);
            const dimensions = `${template.width}x${template.height}`;
            const category = this.getTemplateCategory(template);
            
            console.log(`${num}. ${name.padEnd(42)} | ${dimensions.padEnd(10)} | ${category}`);

            if (this.options.showDetails) {
                await this.showTemplateDetails(template);
                console.log('');
            }
        }

        console.log('\n📊 Summary:');
        this.printSummary(templates);
    }

    async showTemplateDetails(templateMeta) {
        try {
            console.log('     Loading details...');
            const templateUrl = `${CONFIG.repoUrl}/exported_designs/${templateMeta.id}.json`;
            const template = await this.fetchJSON(templateUrl);

            console.log(`     📝 Description: ${template.description || 'None'}`);
            console.log(`     🏷️  Tags: ${this.getTemplateTags(template).join(', ')}`);
            console.log(`     📐 Aspect Ratio: ${(template.width / template.height).toFixed(2)}`);
            
            if (template.layers) {
                const layerTypes = template.layers.reduce((acc, layer) => {
                    acc[layer.type] = (acc[layer.type] || 0) + 1;
                    return acc;
                }, {});
                
                const layerSummary = Object.entries(layerTypes)
                    .map(([type, count]) => `${count} ${type}`)
                    .join(', ');
                
                console.log(`     🎨 Layers: ${template.layers.length} total (${layerSummary})`);
            }

            const hasImages = template.layers?.some(l => l.type === 'image') || false;
            const complexity = this.getTemplateComplexity(template);
            console.log(`     ⚡ Complexity: ${complexity} ${hasImages ? '| Has Images' : ''}`);

        } catch (error) {
            console.log(`     ❌ Could not load details: ${error.message}`);
        }
    }

    async analyzeTemplates(templates) {
        console.log(`📊 Analyzing ${templates.length} templates...\n`);

        const analysis = {
            categories: {},
            dimensions: {},
            aspectRatios: {},
            layerTypes: {},
            complexity: { simple: 0, medium: 0, complex: 0 },
            withImages: 0,
            totalLayers: 0
        };

        let processed = 0;
        for (const templateMeta of templates) {
            try {
                processed++;
                if (processed % 10 === 0) {
                    console.log(`   Processed ${processed}/${templates.length} templates...`);
                }

                const templateUrl = `${CONFIG.repoUrl}/exported_designs/${templateMeta.id}.json`;
                const template = await this.fetchJSON(templateUrl);

                // Categorize
                const category = this.getTemplateCategory(template);
                analysis.categories[category] = (analysis.categories[category] || 0) + 1;

                // Dimensions
                const dim = `${template.width}x${template.height}`;
                analysis.dimensions[dim] = (analysis.dimensions[dim] || 0) + 1;

                // Aspect ratios
                const ratio = Math.round((template.width / template.height) * 10) / 10;
                analysis.aspectRatios[ratio] = (analysis.aspectRatios[ratio] || 0) + 1;

                // Layer analysis
                if (template.layers) {
                    analysis.totalLayers += template.layers.length;
                    
                    template.layers.forEach(layer => {
                        analysis.layerTypes[layer.type] = (analysis.layerTypes[layer.type] || 0) + 1;
                    });

                    // Complexity
                    const complexity = this.getTemplateComplexity(template);
                    analysis.complexity[complexity]++;

                    // Images
                    if (template.layers.some(l => l.type === 'image')) {
                        analysis.withImages++;
                    }
                }

            } catch (error) {
                console.log(`   ⚠️  Could not analyze ${templateMeta.id}: ${error.message}`);
            }
        }

        this.printAnalysis(analysis, processed);
    }

    printAnalysis(analysis, total) {
        console.log(`\n📊 Analysis Results (${total} templates):`);
        console.log('='.repeat(50));

        // Categories
        console.log('\n📁 Categories:');
        Object.entries(analysis.categories)
            .sort(([,a], [,b]) => b - a)
            .forEach(([category, count]) => {
                const percentage = ((count / total) * 100).toFixed(1);
                console.log(`   ${category.padEnd(15)}: ${count.toString().padStart(3)} (${percentage}%)`);
            });

        // Top dimensions
        console.log('\n📐 Top Dimensions:');
        Object.entries(analysis.dimensions)
            .sort(([,a], [,b]) => b - a)
            .slice(0, 10)
            .forEach(([dim, count]) => {
                const percentage = ((count / total) * 100).toFixed(1);
                console.log(`   ${dim.padEnd(15)}: ${count.toString().padStart(3)} (${percentage}%)`);
            });

        // Aspect ratios
        console.log('\n📏 Aspect Ratios:');
        Object.entries(analysis.aspectRatios)
            .sort(([,a], [,b]) => b - a)
            .slice(0, 8)
            .forEach(([ratio, count]) => {
                const percentage = ((count / total) * 100).toFixed(1);
                let ratioName = '';
                if (ratio == '1.0') ratioName = '(Square)';
                else if (ratio == '1.8') ratioName = '(16:9)';
                else if (ratio == '1.3') ratioName = '(4:3)';
                else if (ratio == '0.6') ratioName = '(9:16)';
                
                console.log(`   ${ratio.toString().padEnd(8)} ${ratioName.padEnd(8)}: ${count.toString().padStart(3)} (${percentage}%)`);
            });

        // Layer types
        console.log('\n🎨 Layer Types:');
        Object.entries(analysis.layerTypes)
            .sort(([,a], [,b]) => b - a)
            .forEach(([type, count]) => {
                console.log(`   ${type.padEnd(15)}: ${count.toString().padStart(4)} layers`);
            });

        // Complexity
        console.log('\n⚡ Complexity Distribution:');
        Object.entries(analysis.complexity).forEach(([level, count]) => {
            const percentage = ((count / total) * 100).toFixed(1);
            console.log(`   ${level.padEnd(15)}: ${count.toString().padStart(3)} (${percentage}%)`);
        });

        // Summary stats
        const avgLayers = (analysis.totalLayers / total).toFixed(1);
        const withImagesPercent = ((analysis.withImages / total) * 100).toFixed(1);
        
        console.log('\n📈 Summary Statistics:');
        console.log(`   Average layers per template: ${avgLayers}`);
        console.log(`   Templates with images: ${analysis.withImages} (${withImagesPercent}%)`);
        console.log(`   Total layers across all templates: ${analysis.totalLayers}`);
    }

    printSummary(templates) {
        const categories = {};
        const dimensions = {};

        templates.forEach(template => {
            const category = this.getTemplateCategory(template);
            categories[category] = (categories[category] || 0) + 1;

            const dim = `${template.width}x${template.height}`;
            dimensions[dim] = (dimensions[dim] || 0) + 1;
        });

        console.log('\nCategories:');
        Object.entries(categories)
            .sort(([,a], [,b]) => b - a)
            .forEach(([category, count]) => {
                console.log(`  ${category}: ${count}`);
            });

        console.log('\nTop Dimensions:');
        Object.entries(dimensions)
            .sort(([,a], [,b]) => b - a)
            .slice(0, 5)
            .forEach(([dim, count]) => {
                console.log(`  ${dim}: ${count}`);
            });
    }

    getTemplateCategory(templateData) {
        const { width, height } = templateData;
        const title = (templateData.title || templateData.name || '').toLowerCase();

        if (width === 1080 && height === 1080) return 'social-media';
        if (width === 1080 && height === 1920) return 'social-media';
        if (width === 1200 && height === 630) return 'social-media';
        if (width === 1920 && height === 1080) return 'presentation';
        if (width === 1280 && height === 720) return 'presentation';
        if (width >= 2480 && height >= 3508) return 'print';
        
        if (title.includes('logo')) return 'logo';
        if (title.includes('business card')) return 'print';
        if (title.includes('flyer') || title.includes('poster')) return 'print';
        if (title.includes('web') || title.includes('banner')) return 'web-graphics';
        
        return 'marketing';
    }

    getTemplateTags(templateData) {
        const tags = [];
        const { width, height } = templateData;
        const title = (templateData.title || templateData.name || '').toLowerCase();

        const ratio = width / height;
        if (Math.abs(ratio - 1) < 0.1) tags.push('square');
        else if (ratio > 1.5) tags.push('landscape');
        else if (ratio < 0.7) tags.push('portrait');

        if (width >= 1920) tags.push('hd');
        if (width === 1080) tags.push('standard');

        const contentKeywords = ['business', 'modern', 'minimal', 'creative', 'professional'];
        contentKeywords.forEach(keyword => {
            if (title.includes(keyword)) tags.push(keyword);
        });

        return tags.slice(0, 5);
    }

    getTemplateComplexity(template) {
        if (!template.layers) return 'simple';
        
        const layerCount = template.layers.length;
        const hasImages = template.layers.some(l => l.type === 'image');
        const hasShapes = template.layers.some(l => l.type === 'shape');
        
        if (layerCount <= 3) return 'simple';
        if (layerCount <= 8 && !hasImages) return 'medium';
        if (layerCount > 8 || hasImages || hasShapes) return 'complex';
        
        return 'medium';
    }

    async fetchJSON(url) {
        return new Promise((resolve, reject) => {
            https.get(url, (res) => {
                if (res.statusCode !== 200) {
                    reject(new Error(`HTTP ${res.statusCode}`));
                    return;
                }
                
                let data = '';
                res.on('data', chunk => data += chunk);
                res.on('end', () => {
                    try {
                        resolve(JSON.parse(data));
                    } catch (e) {
                        reject(new Error(`Invalid JSON: ${e.message}`));
                    }
                });
            }).on('error', reject);
        });
    }
}

// CLI argument parsing
function parseArgs() {
    const args = process.argv.slice(2);
    const options = {};

    for (let i = 0; i < args.length; i++) {
        switch (args[i]) {
            case '--limit':
                options.limit = parseInt(args[++i], 10);
                break;
            case '--category':
                options.category = args[++i];
                break;
            case '--details':
                options.showDetails = true;
                break;
            case '--analyze':
                options.analyze = true;
                break;
            case '--help':
                console.log(`
Template Repository Browser

Usage: node browse-templates.js [options]

Options:
  --limit <number>     Limit number of templates to show
  --category <name>    Filter by category
  --details           Show detailed information for each template
  --analyze           Perform statistical analysis of templates
  --help              Show this help

Categories:
  social-media, presentation, print, logo, web-graphics, marketing

Examples:
  node browse-templates.js --limit 20
  node browse-templates.js --category social-media --details
  node browse-templates.js --analyze
  node browse-templates.js --category presentation --limit 10 --details
                `);
                process.exit(0);
            default:
                if (args[i].startsWith('--')) {
                    console.error(`Unknown option: ${args[i]}`);
                    process.exit(1);
                }
        }
    }

    return options;
}

// Main execution
if (require.main === module) {
    const options = parseArgs();
    const browser = new TemplateBrowser(options);
    
    browser.run().catch(error => {
        console.error('\n💥 Browser failed:', error.message);
        process.exit(1);
    });
}

module.exports = { TemplateBrowser };
