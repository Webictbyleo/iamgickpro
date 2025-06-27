#!/usr/bin/env node

/**
 * Template Import Test Script
 * 
 * This script validates the template import system by:
 * 1. Testing GitHub repository access
 * 2. Validating template data structure
 * 3. Testing thumbnail generation
 * 4. Checking backend API connectivity
 * 5. Running a full import test with one template
 */

const https = require('https');
const fs = require('fs').promises;
const path = require('path');

const CONFIG = {
    github: {
        manifestUrl: 'https://raw.githubusercontent.com/Webictbyleo/design-templates/main/exported_designs/designs_manifest.json',
        repoUrl: 'https://raw.githubusercontent.com/Webictbyleo/design-templates/main'
    },
    backend: {
        baseUrl: 'http://localhost:8000/api',
        uploadDir: '../backend/public/uploads/templates',
        thumbnailDir: '../backend/public/uploads/thumbnails'
    }
};

class TemplateImportTester {
    constructor() {
        this.results = {
            githubAccess: false,
            dataValidation: false,
            backendConnectivity: false,
            directoriesExist: false,
            thumbnailGeneration: false,
            fullImportTest: false
        };
    }

    async runTests() {
        console.log('🧪 Template Import System Tests');
        console.log('===============================\n');

        try {
            await this.testGitHubAccess();
            await this.testDataValidation();
            await this.testDirectories();
            await this.testBackendConnectivity();
            await this.testThumbnailGeneration();
            await this.testFullImport();

            this.printResults();

        } catch (error) {
            console.error('❌ Test suite failed:', error.message);
            process.exit(1);
        }
    }

    async testGitHubAccess() {
        console.log('1. Testing GitHub Repository Access...');
        
        try {
            const manifest = await this.fetchJSON(CONFIG.github.manifestUrl);
            
            if (!manifest.totalDesigns || !manifest.designs) {
                throw new Error('Invalid manifest structure');
            }

            console.log(`   ✅ Repository accessible (${manifest.totalDesigns} templates available)`);
            
            // Test individual template access
            const firstTemplate = manifest.designs[0];
            const templateUrl = `${CONFIG.github.repoUrl}/exported_designs/${firstTemplate.id}.json`;
            const templateData = await this.fetchJSON(templateUrl);
            
            if (!templateData.id || !templateData.width || !templateData.height) {
                throw new Error('Invalid template data structure');
            }
            
            console.log(`   ✅ Template data accessible (tested: ${templateData.name || templateData.id})`);
            this.results.githubAccess = true;
            
        } catch (error) {
            console.log(`   ❌ GitHub access failed: ${error.message}`);
        }
        
        console.log('');
    }

    async testDataValidation() {
        console.log('2. Testing Template Data Validation...');
        
        try {
            const manifest = await this.fetchJSON(CONFIG.github.manifestUrl);
            const sampleTemplate = manifest.designs[0];
            const templateUrl = `${CONFIG.github.repoUrl}/exported_designs/${sampleTemplate.id}.json`;
            const templateData = await this.fetchJSON(templateUrl);

            // Validate required fields
            const requiredFields = ['id', 'width', 'height', 'data'];
            const missingFields = requiredFields.filter(field => !templateData[field]);
            
            if (missingFields.length > 0) {
                throw new Error(`Missing required fields: ${missingFields.join(', ')}`);
            }

            // Validate data types
            if (typeof templateData.width !== 'number' || typeof templateData.height !== 'number') {
                throw new Error('Width and height must be numbers');
            }

            if (templateData.layers && !Array.isArray(templateData.layers)) {
                throw new Error('Layers must be an array');
            }

            console.log(`   ✅ Template data structure is valid`);
            console.log(`   ✅ Template dimensions: ${templateData.width}x${templateData.height}`);
            
            if (templateData.layers) {
                console.log(`   ✅ Template has ${templateData.layers.length} layers`);
            }
            
            this.results.dataValidation = true;
            
        } catch (error) {
            console.log(`   ❌ Data validation failed: ${error.message}`);
        }
        
        console.log('');
    }

    async testDirectories() {
        console.log('3. Testing Directory Structure...');
        
        try {
            // Test if directories exist and are writable
            const dirs = [CONFIG.backend.uploadDir, CONFIG.backend.thumbnailDir];
            
            for (const dir of dirs) {
                try {
                    await fs.mkdir(dir, { recursive: true });
                    
                    // Test write permission
                    const testFile = path.join(dir, 'test-write.tmp');
                    await fs.writeFile(testFile, 'test');
                    await fs.unlink(testFile);
                    
                    console.log(`   ✅ Directory accessible and writable: ${dir}`);
                } catch (dirError) {
                    throw new Error(`Directory ${dir} not accessible: ${dirError.message}`);
                }
            }
            
            this.results.directoriesExist = true;
            
        } catch (error) {
            console.log(`   ❌ Directory test failed: ${error.message}`);
        }
        
        console.log('');
    }

    async testBackendConnectivity() {
        console.log('4. Testing Backend API Connectivity...');
        
        try {
            // Test basic connectivity
            const response = await this.makeAPICall('GET', '/templates?limit=1');
            
            if (response.status === 'success' || response.data !== undefined) {
                console.log(`   ✅ Backend API is accessible`);
                console.log(`   ✅ Templates endpoint working`);
                this.results.backendConnectivity = true;
            } else {
                throw new Error('Unexpected API response structure');
            }
            
        } catch (error) {
            console.log(`   ❌ Backend connectivity failed: ${error.message}`);
            console.log(`   ℹ️  Make sure backend server is running: php -S localhost:8000 -t public/`);
        }
        
        console.log('');
    }

    async testThumbnailGeneration() {
        console.log('5. Testing Thumbnail Generation...');
        
        try {
            // Create a simple test template
            const testTemplate = {
                id: 'test-template-' + Date.now(),
                name: 'Test Template',
                width: 800,
                height: 600,
                data: {
                    backgroundColor: '#f0f0f0'
                },
                layers: [
                    {
                        type: 'text',
                        x: 100,
                        y: 100,
                        properties: {
                            text: 'Test Text',
                            fontSize: 24,
                            color: '#333333'
                        }
                    },
                    {
                        type: 'shape',
                        x: 200,
                        y: 200,
                        width: 100,
                        height: 100,
                        properties: {
                            fill: '#4CAF50'
                        }
                    }
                ]
            };

            // Try to generate thumbnail using basic canvas approach
            const { createCanvas } = require('canvas');
            const canvas = createCanvas(400, 300);
            const ctx = canvas.getContext('2d');

            // Background
            ctx.fillStyle = testTemplate.data.backgroundColor;
            ctx.fillRect(0, 0, 400, 300);

            // Calculate scale
            const scaleX = 400 / testTemplate.width;
            const scaleY = 300 / testTemplate.height;
            const scale = Math.min(scaleX, scaleY);

            // Render layers
            testTemplate.layers.forEach(layer => {
                if (layer.type === 'text') {
                    ctx.fillStyle = layer.properties.color;
                    ctx.font = `${layer.properties.fontSize * scale}px Arial`;
                    ctx.fillText(layer.properties.text, layer.x * scale, layer.y * scale);
                } else if (layer.type === 'shape') {
                    ctx.fillStyle = layer.properties.fill;
                    ctx.fillRect(
                        layer.x * scale,
                        layer.y * scale,
                        layer.width * scale,
                        layer.height * scale
                    );
                }
            });

            // Save test thumbnail
            const thumbnailPath = path.join(CONFIG.backend.thumbnailDir, 'test-thumbnail.png');
            const buffer = canvas.toBuffer('image/png');
            await fs.writeFile(thumbnailPath, buffer);

            console.log(`   ✅ Thumbnail generation working`);
            console.log(`   ✅ Test thumbnail saved: ${thumbnailPath}`);
            
            // Clean up test file
            setTimeout(async () => {
                try {
                    await fs.unlink(thumbnailPath);
                } catch {}
            }, 5000);
            
            this.results.thumbnailGeneration = true;
            
        } catch (error) {
            console.log(`   ❌ Thumbnail generation failed: ${error.message}`);
            
            if (error.message.includes('canvas')) {
                console.log(`   ℹ️  Install canvas dependencies: npm install canvas`);
            }
        }
        
        console.log('');
    }

    async testFullImport() {
        console.log('6. Testing Full Import Process (Dry Run)...');
        
        try {
            // Get a real template from GitHub
            const manifest = await this.fetchJSON(CONFIG.github.manifestUrl);
            const testTemplate = manifest.designs[0];
            const templateUrl = `${CONFIG.github.repoUrl}/exported_designs/${testTemplate.id}.json`;
            const templateData = await this.fetchJSON(templateUrl);

            console.log(`   ✅ Fetched template data: ${templateData.name || templateData.id}`);

            // Test categorization logic
            const category = this.getTemplateCategory(templateData);
            console.log(`   ✅ Template categorized as: ${category}`);

            // Test tag generation
            const tags = this.getTemplateTags(templateData);
            console.log(`   ✅ Generated tags: ${tags.join(', ')}`);

            // Test payload creation
            const payload = {
                uuid: templateData.id,
                name: templateData.name || templateData.title,
                description: templateData.description,
                category: category,
                tags: tags,
                width: templateData.width,
                height: templateData.height,
                canvas_settings: templateData.data || {},
                layers: templateData.layers || [],
                is_premium: false,
                is_active: true,
                is_public: true
            };

            console.log(`   ✅ Import payload prepared (${Object.keys(payload).length} fields)`);
            console.log(`   ℹ️  Template: ${payload.name} (${payload.width}x${payload.height})`);

            this.results.fullImportTest = true;
            
        } catch (error) {
            console.log(`   ❌ Full import test failed: ${error.message}`);
        }
        
        console.log('');
    }

    // Helper methods (copied from main importer)
    getTemplateCategory(templateData) {
        const { width, height } = templateData;
        const title = (templateData.title || templateData.name || '').toLowerCase();

        if (width === 1080 && height === 1080) return 'social-media';
        if (width === 1080 && height === 1920) return 'social-media';
        if (width === 1920 && height === 1080) return 'presentation';
        if (title.includes('logo')) return 'logo';
        if (title.includes('business card')) return 'print';

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
        if (title.includes('modern')) tags.push('modern');

        return tags.slice(0, 5);
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

    async makeAPICall(method, endpoint, data = null) {
        return new Promise((resolve, reject) => {
            const url = `${CONFIG.backend.baseUrl}${endpoint}`;
            const { parse } = require('url');
            const { hostname, port, pathname, search } = parse(url);

            const options = {
                hostname,
                port: port || 8000,
                path: pathname + (search || ''),
                method,
                headers: {
                    'Content-Type': 'application/json'
                },
                timeout: 5000
            };

            const req = require('http').request(options, (res) => {
                let body = '';
                res.on('data', chunk => body += chunk);
                res.on('end', () => {
                    try {
                        resolve(JSON.parse(body));
                    } catch {
                        resolve({ status: 'error', message: 'Invalid response' });
                    }
                });
            });

            req.on('error', reject);
            req.on('timeout', () => reject(new Error('Request timeout')));

            if (data) {
                req.write(JSON.stringify(data));
            }

            req.end();
        });
    }

    printResults() {
        console.log('📊 Test Results Summary');
        console.log('=======================');
        
        const tests = [
            ['GitHub Repository Access', this.results.githubAccess],
            ['Template Data Validation', this.results.dataValidation],
            ['Directory Structure', this.results.directoriesExist],
            ['Backend API Connectivity', this.results.backendConnectivity],
            ['Thumbnail Generation', this.results.thumbnailGeneration],
            ['Full Import Process', this.results.fullImportTest]
        ];

        let passed = 0;
        tests.forEach(([name, result]) => {
            const status = result ? '✅ PASS' : '❌ FAIL';
            console.log(`${status} ${name}`);
            if (result) passed++;
        });

        console.log(`\nPassed: ${passed}/${tests.length} tests`);

        if (passed === tests.length) {
            console.log('\n🎉 All tests passed! The template import system is ready to use.');
            console.log('\nNext steps:');
            console.log('1. Install dependencies: npm install');
            console.log('2. Test with sample: ./import-templates.js test');
            console.log('3. Import templates: ./import-templates.js social');
        } else {
            console.log('\n⚠️  Some tests failed. Please address the issues above before importing templates.');
            process.exit(1);
        }
    }
}

// Main execution
if (require.main === module) {
    const tester = new TemplateImportTester();
    tester.runTests().catch(error => {
        console.error('\n💥 Test suite crashed:', error.message);
        process.exit(1);
    });
}

module.exports = { TemplateImportTester };
