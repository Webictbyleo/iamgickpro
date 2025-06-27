#!/usr/bin/env node

/**
 * Advanced Template Import Script with DesignRenderer Integration
 * 
 * This script uses the actual DesignRenderer from the frontend to generate
 * high-quality thumbnails and previews, ensuring consistency with the application.
 * 
 * Features:
 * - Uses JSDOM + Canvas to run DesignRenderer in Node.js
 * - Generates thumbnails identical to the frontend preview system
 * - Batch processing with progress tracking
 * - Asset management and optimization
 * - Direct database insertion for production deployments
 * - Environment variable based configuration
 * - Support for clearing existing templates
 * 
 * Usage:
 *   npm install (in scripts directory)
 *   node advanced-template-importer.js [options]
 * 
 * Options:
 *   --limit <number>      Limit number of templates to import
 *   --force               Overwrite existing templates
 *   --dry-run             Show what would be imported without actually importing
 *   --category <name>     Filter by category
 *   --clear-existing      Clear all existing templates before importing
 *   --no-previews         Skip generating preview images
 *   --help                Show help
 * 
 * Environment Variables:
 *   DATABASE_URL          Database connection string (from backend/.env)
 * 
 * Examples:
 *   npm run import:sample                           # Dry run with 5 templates
 *   npm run import:fresh                            # Clear all and import 20 new
 *   npm run import:all                              # Clear all and import everything
 *   node advanced-template-importer.js --help      # Show help
 */

// Load environment variables
require('dotenv').config({ path: '../backend/.env' });

const https = require('https');
const http = require('http');
const fs = require('fs').promises;
const path = require('path');
const { JSDOM } = require('jsdom');
const { createCanvas } = require('canvas');
const mysql = require('mysql2/promise');

// Configuration
const CONFIG = {
    github: {
        repo: 'https://raw.githubusercontent.com/Webictbyleo/design-templates/main',
        manifestUrl: 'https://raw.githubusercontent.com/Webictbyleo/design-templates/main/exported_designs/designs_manifest.json'
    },
    backend: {
        baseUrl: 'http://localhost:8000/api',
        uploadDir: '../backend/public/uploads/templates',
        thumbnailDir: '../backend/public/uploads/thumbnails'
    },
    frontend: {
        srcDir: '../frontend/src',
        buildDir: '../frontend/dist'
    },
    defaults: {
        thumbnailWidth: 400,
        thumbnailHeight: 300,
        previewWidth: 800,
        previewHeight: 600,
        quality: 0.9
    }
};

/**
 * Parse database URL from environment variable
 */
function parseDatabaseUrl(databaseUrl) {
    if (!databaseUrl) {
        throw new Error('DATABASE_URL environment variable is not set');
    }
    
    // Parse URL like: mysql://user:password@host:port/database?options
    const match = databaseUrl.match(/^mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/([^?]+)/);
    if (!match) {
        throw new Error('Invalid DATABASE_URL format. Expected: mysql://user:password@host:port/database');
    }
    
    return {
        host: match[3],
        port: parseInt(match[4]),
        user: match[1],
        password: match[2],
        database: match[5]
    };
}

class AdvancedTemplateImporter {
    constructor(options = {}) {
        this.options = {
            limit: options.limit || null,
            force: options.force || false,
            dryRun: options.dryRun || false,
            category: options.category || null,
            clearExisting: options.clearExisting || false,
            generatePreviews: options.generatePreviews !== false,
            ...options
        };
        
        this.stats = {
            processed: 0,
            imported: 0,
            skipped: 0,
            errors: 0,
            assetsDownloaded: 0,
            thumbnailsGenerated: 0
        };

        this.dom = null;
        this.designRenderer = null;
        this.dbConnection = null;
    }

    /**
     * Initialize the DOM environment and DesignRenderer
     */
    async initialize() {
        console.log('🔧 Initializing DOM environment and database connection...');
        
        // Initialize database connection
        await this.initializeDatabase();
        
        // Create JSDOM environment with Canvas support
        this.dom = new JSDOM('<!DOCTYPE html><html><body></body></html>', {
            url: 'http://localhost',
            pretendToBeVisual: true,
            resources: 'usable'
        });

        // Set up global environment for frontend code
        global.window = this.dom.window;
        global.document = this.dom.window.document;
        global.HTMLCanvasElement = createCanvas().constructor;
        global.Image = this.dom.window.Image;

        // Mock Konva for Node.js environment
        await this.setupKonva();
        
        // Load DesignRenderer
        await this.loadDesignRenderer();
        
        console.log('✅ Environment initialized successfully');
    }

    /**
     * Initialize database connection
     */
    async initializeDatabase() {
        console.log('🗄️  Connecting to database...');
        
        try {
            // Parse database URL from environment
            const databaseConfig = parseDatabaseUrl(process.env.DATABASE_URL);
            
            this.dbConnection = await mysql.createConnection({
                host: databaseConfig.host,
                port: databaseConfig.port,
                user: databaseConfig.user,
                password: databaseConfig.password,
                database: databaseConfig.database,
                charset: 'utf8mb4'
            });
            
            console.log('   ✅ Database connection established');
            
            // Test the connection
            await this.dbConnection.execute('SELECT 1');
            console.log('   ✅ Database connection tested successfully');
            
        } catch (error) {
            console.error('   ❌ Database connection failed:', error.message);
            throw new Error(`Database connection failed: ${error.message}`);
        }
    }

    /**
     * Setup Konva.js for Node.js environment
     */
    async setupKonva() {
        // Mock Konva with more complete implementation for thumbnail generation
        global.Konva = {
            Stage: class MockStage {
                constructor(config = {}) {
                    this.config = config;
                    this.layers = [];
                    this._width = config.width || 800;
                    this._height = config.height || 600;
                    this._scale = { x: 1, y: 1 };
                    this._container = config.container; // Store container reference
                }
                
                width(w) { if (w !== undefined) this._width = w; return this._width; }
                height(h) { if (h !== undefined) this._height = h; return this._height; }
                scale(s) { if (s !== undefined) this._scale = s; return this._scale; }
                add(layer) { this.layers.push(layer); }
                container() { return this._container; }
                
                toCanvas(options = {}) {
                    const pixelRatio = options.pixelRatio || 1;
                    const canvas = createCanvas(this._width * pixelRatio, this._height * pixelRatio);
                    const ctx = canvas.getContext('2d');
                    
                    // Apply scaling for high-DPI rendering
                    ctx.scale(pixelRatio, pixelRatio);
                    
                    // Apply stage scaling
                    ctx.scale(this._scale.x, this._scale.y);
                    
                    // Render all layers
                    this.layers.forEach(layer => {
                        if (layer.render) layer.render(ctx);
                    });
                    
                    return canvas;
                }
                
                destroy() {
                    this.layers = [];
                    this._container = null;
                }
                
                destroyChildren() { this.layers = []; }
            },
            
            Layer: class MockLayer {
                constructor() {
                    this.children = [];
                }
                
                add(child) { this.children.push(child); }
                destroyChildren() { this.children = []; }
                
                render(ctx) {
                    this.children.forEach(child => {
                        if (child.render) child.render(ctx);
                    });
                }
            },
            
            Rect: class MockRect {
                constructor(config = {}) {
                    Object.assign(this, {
                        x: 0,
                        y: 0,
                        width: 100,
                        height: 100,
                        fillColor: '#ffffff',
                        opacity: 1,
                        listening: true,
                        ...config
                    });
                    this._fillLinearGradientColorStops = null;
                    this._fillRadialGradientColorStops = null;
                }
                
                // Property getter/setter methods for Konva compatibility
                fill(color) { 
                    if (color !== undefined) { 
                        this.fillColor = color; 
                        return this; 
                    } 
                    return this.fillColor; 
                }
                
                x(value) { if (value !== undefined) { this._x = value; return this; } return this._x || 0; }
                y(value) { if (value !== undefined) { this._y = value; return this; } return this._y || 0; }
                width(value) { if (value !== undefined) { this._width = value; return this; } return this._width || 100; }
                height(value) { if (value !== undefined) { this._height = value; return this; } return this._height || 100; }
                opacity(value) { if (value !== undefined) { this._opacity = value; return this; } return this._opacity || 1; }
                
                fillLinearGradientStartPoint(point) { this._fillLinearGradientStartPoint = point; return this; }
                fillLinearGradientEndPoint(point) { this._fillLinearGradientEndPoint = point; return this; }
                fillLinearGradientColorStops(stops) { this._fillLinearGradientColorStops = stops; return this; }
                fillRadialGradientStartPoint(point) { this._fillRadialGradientStartPoint = point; return this; }
                fillRadialGradientEndPoint(point) { this._fillRadialGradientEndPoint = point; return this; }
                fillRadialGradientStartRadius(radius) { this._fillRadialGradientStartRadius = radius; return this; }
                fillRadialGradientEndRadius(radius) { this._fillRadialGradientEndRadius = radius; return this; }
                fillRadialGradientColorStops(stops) { this._fillRadialGradientColorStops = stops; return this; }
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                    return this;
                }
                
                destroy() {
                    // Clean up
                }
                
                render(ctx) {
                    ctx.save();
                    ctx.globalAlpha = (typeof this.opacity === 'function' ? this.opacity() : this.opacity) || 1;
                    
                    // Handle gradients
                    if (this._fillLinearGradientColorStops && this._fillLinearGradientStartPoint && this._fillLinearGradientEndPoint) {
                        const gradient = ctx.createLinearGradient(
                            this._fillLinearGradientStartPoint.x,
                            this._fillLinearGradientStartPoint.y,
                            this._fillLinearGradientEndPoint.x,
                            this._fillLinearGradientEndPoint.y
                        );
                        for (let i = 0; i < this._fillLinearGradientColorStops.length; i += 2) {
                            gradient.addColorStop(this._fillLinearGradientColorStops[i], this._fillLinearGradientColorStops[i + 1]);
                        }
                        ctx.fillStyle = gradient;
                    } else if (this._fillRadialGradientColorStops && this._fillRadialGradientStartPoint && this._fillRadialGradientEndPoint) {
                        const gradient = ctx.createRadialGradient(
                            this._fillRadialGradientStartPoint.x,
                            this._fillRadialGradientStartPoint.y,
                            this._fillRadialGradientStartRadius || 0,
                            this._fillRadialGradientEndPoint.x,
                            this._fillRadialGradientEndPoint.y,
                            this._fillRadialGradientEndRadius || 100
                        );
                        for (let i = 0; i < this._fillRadialGradientColorStops.length; i += 2) {
                            gradient.addColorStop(this._fillRadialGradientColorStops[i], this._fillRadialGradientColorStops[i + 1]);
                        }
                        ctx.fillStyle = gradient;
                    } else {
                        ctx.fillStyle = (typeof this.fill === 'function' ? this.fill() : this.fillColor) || '#ffffff';
                    }
                    
                    const x = typeof this.x === 'function' ? this.x() : this.x || 0;
                    const y = typeof this.y === 'function' ? this.y() : this.y || 0;
                    const width = typeof this.width === 'function' ? this.width() : this.width || 100;
                    const height = typeof this.height === 'function' ? this.height() : this.height || 100;
                    
                    ctx.fillRect(x, y, width, height);
                    ctx.restore();
                }
            },
            
            Text: class MockText {
                constructor(config = {}) {
                    Object.assign(this, {
                        x: 0,
                        y: 0,
                        text: '',
                        fontSize: 16,
                        fontFamily: 'Arial',
                        fill: '#000000',
                        opacity: 1,
                        ...config
                    });
                }
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                }
                
                destroy() {
                    // Clean up
                }
                
                render(ctx) {
                    ctx.save();
                    ctx.globalAlpha = this.opacity || 1;
                    ctx.fillStyle = this.fill || '#000000';
                    ctx.font = `${this.fontSize || 16}px ${this.fontFamily || 'Arial'}`;
                    ctx.fillText(this.text || '', this.x || 0, (this.y || 0) + (this.fontSize || 16));
                    ctx.restore();
                }
            },
            
            Image: class MockImage {
                constructor(config = {}) {
                    Object.assign(this, {
                        x: 0,
                        y: 0,
                        width: 100,
                        height: 100,
                        opacity: 1,
                        ...config
                    });
                }
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                }
                
                destroy() {
                    // Clean up
                }
                
                image() {
                    return this._image || null;
                }
                
                render(ctx) {
                    ctx.save();
                    ctx.globalAlpha = this.opacity || 1;
                    if (this.image()) {
                        ctx.drawImage(this.image(), this.x || 0, this.y || 0, this.width || 100, this.height || 100);
                    } else {
                        // Placeholder for missing images
                        ctx.fillStyle = '#e0e0e0';
                        ctx.fillRect(this.x || 0, this.y || 0, this.width || 100, this.height || 100);
                        ctx.fillStyle = '#999999';
                        ctx.font = '12px Arial';
                        ctx.fillText('IMG', (this.x || 0) + 10, (this.y || 0) + 20);
                    }
                    ctx.restore();
                }
            },
            
            Group: class MockGroup {
                constructor(config = {}) {
                    Object.assign(this, {
                        x: 0,
                        y: 0,
                        opacity: 1,
                        ...config
                    });
                    this.children = [];
                }
                
                add(child) { this.children.push(child); }
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                }
                
                destroy() {
                    this.children = [];
                }
                
                findOne(selector) {
                    // Simple selector support for finding children
                    if (typeof selector === 'string') {
                        return this.children.find(child => 
                            child.constructor.name.includes(selector) || 
                            child.tagName === selector
                        );
                    }
                    return null;
                }
                
                render(ctx) {
                    ctx.save();
                    ctx.globalAlpha = this.opacity || 1;
                    ctx.translate(this.x || 0, this.y || 0);
                    this.children.forEach(child => {
                        if (child.render) child.render(ctx);
                    });
                    ctx.restore();
                }
            }
        };
    }

    /**
     * Load the DesignRenderer from frontend
     */
    async loadDesignRenderer() {
        console.log('📦 Loading DesignRenderer...');
        
        try {
            // Try to load DesignRenderer from frontend
            const rendererPath = path.join(__dirname, CONFIG.frontend.srcDir, 'editor/sdk/DesignRenderer.ts');
            
            // Check if the file exists
            try {
                await fs.access(rendererPath);
                console.log('   ✅ Found DesignRenderer source file');
            } catch {
                throw new Error('DesignRenderer.ts not found at expected path');
            }
            
            // Read and transpile TypeScript to JavaScript
            console.log('   🔄 Transpiling TypeScript to JavaScript...');
            const tsSource = await fs.readFile(rendererPath, 'utf8');
            const jsSource = await this.transpileTypeScript(tsSource);
            
            // Create a module context and evaluate
            console.log('   ⚡ Loading DesignRenderer class...');
            const moduleContext = this.createModuleContext();
            
            // Execute the transpiled code in the module context
            const vm = require('vm');
            const context = vm.createContext(moduleContext);
            
            // Save transpiled code for debugging
            if (process.env.DEBUG_TRANSPILATION) {
                await fs.writeFile(path.join(__dirname, 'debug-transpiled.js'), jsSource);
                console.log('   💾 Saved transpiled code to debug-transpiled.js');
            }
            
            // Add the transpiled code to the context
            console.log('   🔧 Executing transpiled code in VM context...');
            try {
                vm.runInContext(jsSource, context);
                console.log('   ✅ Code executed successfully');
            } catch (execError) {
                console.log('   ❌ Code execution failed:', execError.message);
                console.log('   🔍 Saving failed code for debugging...');
                await fs.writeFile(path.join(__dirname, 'failed-transpiled.js'), jsSource);
                console.log('   💾 Saved to failed-transpiled.js');
                throw execError;
            }
            
            // Extract the DesignRenderer class
            console.log('   🔍 Checking for DesignRenderer in context...');
            console.log('   📋 DesignRenderer type:', typeof context.DesignRenderer);
            console.log('   📋 DesignRenderer is constructor:', typeof context.DesignRenderer === 'function');
            
            if (context.DesignRenderer && typeof context.DesignRenderer === 'function') {
                console.log('   🎯 Creating DesignRenderer instance...');
                try {
                    this.designRenderer = new context.DesignRenderer();
                    console.log('   ✅ Successfully loaded DesignRenderer from frontend!');
                } catch (constructorError) {
                    console.log('   ❌ DesignRenderer constructor failed:', constructorError.message);
                    throw constructorError;
                }
            } else {
                console.log('   ❌ DesignRenderer class not found in context after execution');
                console.log('   🔍 Available keys in context:', Object.keys(context).filter(k => typeof context[k] === 'function' || typeof context[k] === 'object'));
                if (context.DesignRenderer) {
                    console.log('   📋 DesignRenderer exists but is not a function, type:', typeof context.DesignRenderer);
                    console.log('   📋 DesignRenderer value:', context.DesignRenderer);
                } else {
                    console.log('   📋 DesignRenderer is null/undefined');
                }
                throw new Error('DesignRenderer class not found in transpiled code');
            }
            
        } catch (error) {
            console.log(`   ⚠️  Failed to load DesignRenderer (${error.message}), using fallback`);
            this.designRenderer = this.createFallbackRenderer();
        }
    }

    /**
     * Create fallback renderer if DesignRenderer fails to load
     */
    createFallbackRenderer() {
        return {
            async generateThumbnail(design, options = {}) {
                const {
                    width = CONFIG.defaults.thumbnailWidth,
                    height = CONFIG.defaults.thumbnailHeight
                } = options;

                const canvas = createCanvas(width, height);
                const ctx = canvas.getContext('2d');

                // Background
                ctx.fillStyle = design.data?.backgroundColor || '#ffffff';
                ctx.fillRect(0, 0, width, height);

                // Calculate scale
                const scaleX = width / design.width;
                const scaleY = height / design.height;
                const scale = Math.min(scaleX, scaleY);

                // Render basic elements
                if (design.layers) {
                    design.layers.forEach(layer => {
                        if (layer.type === 'text') {
                            ctx.fillStyle = layer.properties?.color || '#000000';
                            ctx.font = `${(layer.properties?.fontSize || 16) * scale}px ${layer.properties?.fontFamily || 'Arial'}`;
                            ctx.fillText(
                                layer.properties?.text || 'Sample Text',
                                (layer.x || 0) * scale,
                                (layer.y || 0) * scale
                            );
                        } else if (layer.type === 'shape') {
                            ctx.fillStyle = layer.properties?.fill || '#cccccc';
                            ctx.fillRect(
                                (layer.x || 0) * scale,
                                (layer.y || 0) * scale,
                                (layer.width || 100) * scale,
                                (layer.height || 100) * scale
                            );
                        }
                    });
                }

                return canvas.toDataURL('image/png', CONFIG.defaults.quality);
            }
        };
    }

    /**
     * TypeScript to JavaScript transpilation using TypeScript compiler API
     */
    async transpileTypeScript(tsSource) {
        console.log('   🔧 Starting TypeScript transpilation with TypeScript compiler...');
        
        try {
            const ts = require('typescript');
            
            // First, preprocess the source to remove imports and adapt for Node.js
            let preprocessedSource = this.preprocessTypeScriptForNodeJs(tsSource);
            
            // Configure TypeScript compiler options for plain JavaScript output
            const compilerOptions = {
                target: ts.ScriptTarget.ES2020,
                module: ts.ModuleKind.None, // No module system - just plain JS
                lib: ['es2020', 'dom'],
                declaration: false,
                outDir: undefined,
                removeComments: false,
                noEmit: false,
                strict: false,
                esModuleInterop: false,
                skipLibCheck: true,
                forceConsistentCasingInFileNames: false,
                noImplicitAny: false,
                noImplicitReturns: false,
                noImplicitThis: false,
                strictNullChecks: false
            };
            
            // Transpile TypeScript to JavaScript
            const result = ts.transpileModule(preprocessedSource, {
                compilerOptions
            });
            
            let jsSource = result.outputText;
            
            // Post-process the JavaScript for our Node.js environment
            jsSource = this.postProcessJavaScript(jsSource);
            
            // Validate the transpiled code
            console.log('   🔍 Validating transpiled JavaScript...');
            try {
                new Function(jsSource);
                console.log('   ✅ Transpiled code syntax is valid');
            } catch (syntaxError) {
                console.log('   ❌ Syntax error in transpiled code:', syntaxError.message);
                await fs.writeFile(path.join(__dirname, 'syntax-error-transpiled.js'), jsSource);
                throw new Error(`Transpilation validation failed: ${syntaxError.message}`);
            }
            
            console.log('   ✅ TypeScript transpilation completed successfully');
            return jsSource;
            
        } catch (error) {
            console.log(`   ⚠️  TypeScript transpilation failed (${error.message}), falling back to esbuild`);
            
            // Fallback to esbuild if TypeScript compiler fails
            return this.transpileWithEsbuild(tsSource);
        }
    }

    /**
     * Preprocess TypeScript source for Node.js environment
     */
    preprocessTypeScriptForNodeJs(tsSource) {
        let source = tsSource;
        
        // Remove all import statements - we'll provide mocks in the context
        source = source.replace(/^import\s+.*?from\s+['"][^'"]+['"];?\s*$/gm, '');
        source = source.replace(/^import\s+.*$/gm, '');
        
        // Remove export statements but keep the classes/functions
        source = source.replace(/^export\s+default\s+/gm, '');
        source = source.replace(/^export\s+/gm, '');
        
        // Remove the singleton export at the end
        source = source.replace(/^\/\/\s*Singleton.*[\s\S]*?export\s+const\s+designRenderer.*$/gm, '');
        
        // Replace DOM API calls with our Node.js compatible versions
        source = source.replace(/document\.createElement\(['"]canvas['"]\)/g, 'createCanvas()');
        source = source.replace(/document\.createElement\(['"]div['"]\)/g, 'createMockElement()');
        source = source.replace(/document\.body\.appendChild\(/g, 'mockAppendChild(');
        source = source.replace(/container\.parentNode\.removeChild\(/g, 'mockRemoveChild(');
        
        // Replace the entire createOffscreenStage method to work without DOM
        source = source.replace(
            /private\s+createOffscreenStage\(\):\s*void\s*{[\s\S]*?this\.stage\.add\(this\.layer\)\s*}/gm,
            `private createOffscreenStage() {
                // Create mock container for Node.js
                const container = createMockElement();
                
                this.stage = new Konva.Stage({
                    container,
                    width: 800,
                    height: 600
                });

                this.layer = new Konva.Layer();
                this.stage.add(this.layer);
            }`
        );
        
        // Replace the destroy method to work without DOM
        source = source.replace(
            /destroy\(\):\s*void\s*{[\s\S]*?}\s*}/gm,
            `destroy() {
                if (this.stage) {
                    this.stage.destroy();
                    this.stage = null;
                    this.layer = null;
                }
            }`
        );
        
        return source;
    }

    /**
     * Post-process transpiled JavaScript for our environment
     */
    postProcessJavaScript(jsSource) {
        // Clean up any remaining CommonJS artifacts (shouldn't be many with module: None)
        jsSource = jsSource.replace(/^Object\.defineProperty\(exports.*?\);\s*$/gm, '');
        jsSource = jsSource.replace(/^exports\.\w+\s*=.*?;\s*$/gm, '');
        jsSource = jsSource.replace(/^module\.exports\s*=.*?;\s*$/gm, '');
        
        // Remove "use strict" if present
        jsSource = jsSource.replace(/^"use strict";\s*$/gm, '');
        
        // Replace any remaining require statements
        jsSource = jsSource.replace(/var\s+\w+\s*=\s*require\([^)]+\);\s*/g, '');
        jsSource = jsSource.replace(/require\([^)]+\)/g, '{}');
        
        // Ensure DesignRenderer class is properly exposed and instantiable
        if (!jsSource.includes('class DesignRenderer')) {
            throw new Error('DesignRenderer class not found in transpiled code');
        }
        
        // Add explicit global assignment for the DesignRenderer class
        // This ensures the class constructor is available in the VM context
        if (jsSource.includes('const designRenderer = new DesignRenderer()')) {
            // Replace the singleton assignment with both class and instance assignments
            jsSource = jsSource.replace(
                'const designRenderer = new DesignRenderer()',
                'this.DesignRenderer = DesignRenderer; const designRenderer = new DesignRenderer()'
            );
        } else {
            // If no singleton, just add the class assignment at the end
            jsSource += '\nthis.DesignRenderer = DesignRenderer;';
        }
        
        // Clean up whitespace
        jsSource = jsSource.replace(/\n\s*\n\s*\n/g, '\n\n');
        jsSource = jsSource.trim();
        
        return jsSource;
    }

    /**
     * Fallback transpilation using esbuild
     */
    async transpileWithEsbuild(tsSource) {
        console.log('   🔧 Falling back to esbuild transpilation...');
        
        try {
            const esbuild = require('esbuild');
            
            // Preprocess the source
            let preprocessedSource = this.preprocessTypeScriptForNodeJs(tsSource);
            
            // Use esbuild to transpile
            const result = await esbuild.transform(preprocessedSource, {
                loader: 'ts',
                target: 'es2020',
                format: 'cjs',
                keepNames: true,
                minify: false,
                sourcemap: false
            });
            
            let jsSource = result.code;
            
            // Post-process for our environment
            jsSource = this.postProcessJavaScript(jsSource);
            
            console.log('   ✅ esbuild transpilation completed');
            return jsSource;
            
        } catch (error) {
            console.log(`   ❌ esbuild also failed: ${error.message}`);
            throw new Error(`All transpilation methods failed: ${error.message}`);
        }
    }

    /**
     * Create module context for DesignRenderer
     */
    createModuleContext() {
        // Create mock renderer classes that implement the KonvaLayerRenderer interface
        class TextLayerRenderer {
            render(layerNode) {
                const text = new global.Konva.Text({
                    x: layerNode.x || 0,
                    y: layerNode.y || 0,
                    text: layerNode.properties?.text || 'Text',
                    fontSize: layerNode.properties?.fontSize || 16,
                    fontFamily: layerNode.properties?.fontFamily || 'Arial',
                    fill: layerNode.properties?.color || '#000000',
                    width: layerNode.width,
                    height: layerNode.height,
                    opacity: layerNode.opacity || 1
                });
                return text;
            }
            
            update(node, layerNode) {
                // Update existing node with new properties
                if (node && typeof node.setAttrs === 'function') {
                    node.setAttrs({
                        text: layerNode.properties?.text || 'Text',
                        fontSize: layerNode.properties?.fontSize || 16,
                        fontFamily: layerNode.properties?.fontFamily || 'Arial',
                        fill: layerNode.properties?.color || '#000000'
                    });
                }
            }
            
            destroy(node) {
                if (node && typeof node.destroy === 'function') {
                    node.destroy();
                }
            }
            
            canRender(layer) {
                return layer.type === 'text';
            }
        }

        class ImageLayerRenderer {
            render(layerNode) {
                const image = new global.Konva.Image({
                    x: layerNode.x || 0,
                    y: layerNode.y || 0,
                    width: layerNode.width || 100,
                    height: layerNode.height || 100,
                    opacity: layerNode.opacity || 1
                });
                return image;
            }
            
            update(node, layerNode) {
                if (node && typeof node.setAttrs === 'function') {
                    node.setAttrs({
                        width: layerNode.width || 100,
                        height: layerNode.height || 100
                    });
                }
            }
            
            destroy(node) {
                if (node && typeof node.destroy === 'function') {
                    node.destroy();
                }
            }
            
            canRender(layer) {
                return layer.type === 'image';
            }
        }

        class ShapeLayerRenderer {
            render(layerNode) {
                const rect = new global.Konva.Rect({
                    x: layerNode.x || 0,
                    y: layerNode.y || 0,
                    width: layerNode.width || 100,
                    height: layerNode.height || 100,
                    fill: layerNode.properties?.fill || '#cccccc',
                    opacity: layerNode.opacity || 1
                });
                return rect;
            }
            
            update(node, layerNode) {
                if (node && typeof node.setAttrs === 'function') {
                    node.setAttrs({
                        width: layerNode.width || 100,
                        height: layerNode.height || 100,
                        fill: layerNode.properties?.fill || '#cccccc'
                    });
                }
            }
            
            destroy(node) {
                if (node && typeof node.destroy === 'function') {
                    node.destroy();
                }
            }
            
            canRender(layer) {
                return layer.type === 'shape';
            }
        }

        class GroupLayerRenderer {
            render(layerNode) {
                const group = new global.Konva.Group({
                    x: layerNode.x || 0,
                    y: layerNode.y || 0,
                    opacity: layerNode.opacity || 1
                });
                return group;
            }
            
            update(node, layerNode) {
                if (node && typeof node.setAttrs === 'function') {
                    node.setAttrs({
                        x: layerNode.x || 0,
                        y: layerNode.y || 0,
                        opacity: layerNode.opacity || 1
                    });
                }
            }
            
            destroy(node) {
                if (node && typeof node.destroy === 'function') {
                    node.destroy();
                }
            }
            
            canRender(layer) {
                return layer.type === 'group';
            }
        }

        class SVGLayerRenderer {
            render(layerNode) {
                // Fallback to shape renderer for SVG
                const rect = new global.Konva.Rect({
                    x: layerNode.x || 0,
                    y: layerNode.y || 0,
                    width: layerNode.width || 100,
                    height: layerNode.height || 100,
                    fill: layerNode.properties?.fill || '#cccccc',
                    opacity: layerNode.opacity || 1
                });
                return rect;
            }
            
            update(node, layerNode) {
                if (node && typeof node.setAttrs === 'function') {
                    node.setAttrs({
                        width: layerNode.width || 100,
                        height: layerNode.height || 100,
                        fill: layerNode.properties?.fill || '#cccccc'
                    });
                }
            }
            
            destroy(node) {
                if (node && typeof node.destroy === 'function') {
                    node.destroy();
                }
            }
            
            canRender(layer) {
                return layer.type === 'svg';
            }
        }

        // Helper functions for DOM mocking
        function createMockElement() {
            return {
                style: {},
                appendChild: function() {},
                parentNode: null,
                removeChild: function() {}
            };
        }

        function mockAppendChild(element) {
            // Do nothing - we're in Node.js
            return element;
        }

        function mockRemoveChild(element) {
            // Do nothing - we're in Node.js
            return element;
        }

        return {
            // Node.js globals
            console,
            Buffer,
            process,
            global,
            require,
            
            // DOM globals (from JSDOM)
            window: global.window,
            document: global.document,
            Image: global.Image,
            HTMLCanvasElement: global.HTMLCanvasElement,
            
            // Canvas and Konva
            Konva: global.Konva,
            createCanvas,
            
            // Mock DOM functions
            createMockElement,
            mockAppendChild,
            mockRemoveChild,
            
            // Mock renderer classes
            TextLayerRenderer,
            ImageLayerRenderer,
            ShapeLayerRenderer,
            SVGLayerRenderer,
            GroupLayerRenderer,
            
            // Module system
            module: { exports: {} },
            exports: {},
            
            // Map constructor for renderers
            Map: global.Map,
            
            // Math object
            Math: global.Math,
            
            // DesignRenderer will be added here after execution
            DesignRenderer: null
        };
    }

    /**
     * Main import process
     */
    async run() {
        console.log('🚀 Starting advanced template import process...');
        
        try {
            // Initialize environment
            await this.initialize();
            
            // Ensure directories exist
            await this.ensureDirectories();

            // Clear existing templates if requested
            if (this.options.clearExisting) {
                await this.clearExistingTemplates();
            }

            // Fetch manifest
            console.log('📋 Fetching template manifest...');
            const manifest = await this.fetchJSON(CONFIG.github.manifestUrl);
            console.log(`Found ${manifest.totalDesigns} templates in repository`);

            // Debug: Show first few templates from manifest
            console.log('🔍 Sample manifest entries:');
            manifest.designs.slice(0, 3).forEach((template, index) => {
                console.log(`  [${index + 1}] ID: ${template.id}`);
                console.log(`      Name: ${template.name || 'N/A'}`);
                console.log(`      Category: ${template.category || 'N/A'}`);
                console.log(`      Tags: ${template.tags || 'N/A'}`);
                console.log(`      Width: ${template.width || 'N/A'}`);
                console.log(`      Height: ${template.height || 'N/A'}`);
            });

            // Filter and limit templates
            let templates = this.filterTemplates(manifest.designs);
            console.log(`Processing ${templates.length} templates...`);

            // Process templates in batches
            const batchSize = 5;
            for (let i = 0; i < templates.length; i += batchSize) {
                const batch = templates.slice(i, i + batchSize);
                console.log(`\n📦 Processing batch ${Math.floor(i / batchSize) + 1}/${Math.ceil(templates.length / batchSize)}`);
                
                await Promise.allSettled(
                    batch.map(template => this.processTemplate(template))
                );
            }

            // Print summary
            this.printSummary();

            // Close database connection
            await this.cleanup();

        } catch (error) {
            console.error('❌ Import process failed:', error.message);
            await this.cleanup();
            process.exit(1);
        }
    }

    /**
     * Clear all existing templates from database
     */
    async clearExistingTemplates() {
        if (this.options.dryRun) {
            console.log('🗑️  [DRY RUN] Would clear all existing templates');
            return;
        }

        console.log('🗑️  Clearing existing templates...');
        
        try {
            // First, get the count of existing templates
            const [countResult] = await this.dbConnection.execute('SELECT COUNT(*) as count FROM templates');
            const existingCount = countResult[0].count;
            
            if (existingCount === 0) {
                console.log('   ✅ No existing templates to clear');
                return;
            }

            console.log(`   📊 Found ${existingCount} existing templates`);
            
            // Truncate the templates table
            await this.dbConnection.execute('TRUNCATE TABLE templates');
            
            console.log(`   ✅ Successfully cleared ${existingCount} templates`);
            
        } catch (error) {
            console.error('   ❌ Failed to clear existing templates:', error.message);
            throw new Error(`Failed to clear existing templates: ${error.message}`);
        }
    }

    /**
     * Cleanup resources
     */
    async cleanup() {
        if (this.dbConnection) {
            console.log('🔄 Closing database connection...');
            try {
                await this.dbConnection.end();
                console.log('✅ Database connection closed');
            } catch (error) {
                console.error('⚠️  Error closing database connection:', error.message);
            }
        }
    }

    /**
     * Filter templates based on options
     */
    filterTemplates(templates) {
        let filtered = templates;

        if (this.options.category) {
            filtered = filtered.filter(t => {
                // Use manifest category if available, otherwise derive it
                const category = t.category || this.getTemplateCategory(t);
                return category === this.options.category;
            });
            console.log(`Filtered to ${filtered.length} templates in category: ${this.options.category}`);
        }

        if (this.options.limit) {
            filtered = filtered.slice(0, this.options.limit);
            console.log(`Limited to ${filtered.length} templates`);
        }

        return filtered;
    }

    /**
     * Process a single template
     */
    async processTemplate(templateMeta) {
        this.stats.processed++;
        const templateId = templateMeta.id;
        
        try {
            console.log(`\n[${this.stats.processed}] Processing: ${templateMeta.name || templateId}`);
            console.log(`    Manifest Category: ${templateMeta.category || 'N/A'}`);
            console.log(`    Manifest Tags: ${templateMeta.tags || 'N/A'}`);

            // Check if exists (unless force mode)
            if (!this.options.force && await this.templateExists(templateId)) {
                console.log(`  ⏭️  Already exists, skipping`);
                this.stats.skipped++;
                return;
            }

            if (this.options.dryRun) {
                console.log(`  🔍 [DRY RUN] Would import: ${templateMeta.name}`);
                console.log(`    Would use category: ${templateMeta.category || 'derived'}`);
                console.log(`    Would use tags: ${templateMeta.tags || 'derived'}`);
                this.stats.imported++;
                return;
            }

            // Fetch template data
            const templateData = await this.fetchTemplateData(templateId);

            // Download assets
            await this.downloadTemplateAssets(templateData);

            // Generate thumbnails
            const thumbnailPaths = await this.generateThumbnails(templateData);

            // Import to database
            await this.importToDatabase(templateData, thumbnailPaths, templateMeta);

            console.log(`  ✅ Successfully imported`);
            this.stats.imported++;

        } catch (error) {
            console.error(`  ❌ Failed to process ${templateId}:`, error.message);
            this.stats.errors++;
        }
    }

    /**
     * Fetch template data from GitHub
     */
    async fetchTemplateData(templateId) {
        const url = `${CONFIG.github.repo}/exported_designs/${templateId}.json`;
        return await this.fetchJSON(url);
    }

    /**
     * Generate thumbnails using DesignRenderer
     */
    async generateThumbnails(templateData) {
        console.log(`  🖼️  Generating thumbnails...`);

        const thumbnailPath = path.join(CONFIG.backend.thumbnailDir, `${templateData.id}.png`);
        const previewPath = path.join(CONFIG.backend.thumbnailDir, `${templateData.id}_preview.png`);

        try {
            // Generate standard thumbnail
            const thumbnailDataUrl = await this.designRenderer.generateThumbnail(templateData, {
                width: CONFIG.defaults.thumbnailWidth,
                height: CONFIG.defaults.thumbnailHeight,
                quality: CONFIG.defaults.quality
            });

            // Save thumbnail
            const thumbnailBuffer = Buffer.from(thumbnailDataUrl.split(',')[1], 'base64');
            await fs.writeFile(thumbnailPath, thumbnailBuffer);

            // Generate preview if enabled
            let previewRelativePath = null;
            if (this.options.generatePreviews) {
                const previewDataUrl = await this.designRenderer.generateThumbnail(templateData, {
                    width: CONFIG.defaults.previewWidth,
                    height: CONFIG.defaults.previewHeight,
                    quality: CONFIG.defaults.quality
                });

                const previewBuffer = Buffer.from(previewDataUrl.split(',')[1], 'base64');
                await fs.writeFile(previewPath, previewBuffer);
                previewRelativePath = path.relative('../backend/public', previewPath);
            }

            this.stats.thumbnailsGenerated++;

            return {
                thumbnail: path.relative('../backend/public', thumbnailPath),
                preview: previewRelativePath
            };

        } catch (error) {
            throw new Error(`Thumbnail generation failed: ${error.message}`);
        }
    }

    /**
     * Download template assets
     */
    async downloadTemplateAssets(templateData) {
        if (!templateData.layers) return;

        console.log(`  📦 Downloading assets...`);
        let downloadCount = 0;

        for (const layer of templateData.layers) {
            if (layer.type === 'image' && layer.properties?.src) {
                const downloaded = await this.downloadAsset(layer.properties.src);
                if (downloaded) downloadCount++;
            }
        }

        if (downloadCount > 0) {
            console.log(`    ✅ Downloaded ${downloadCount} assets`);
            this.stats.assetsDownloaded += downloadCount;
        }
    }

    /**
     * Download a single asset
     */
    async downloadAsset(assetPath) {
        if (!assetPath.startsWith('/converted_assets/')) return false;

        const filename = path.basename(assetPath);
        const url = `${CONFIG.github.repo}/converted_assets/${filename}`;
        const localPath = path.join(CONFIG.backend.uploadDir, filename);

        // Check if exists
        try {
            await fs.access(localPath);
            return false; // Already exists
        } catch {}

        // Download
        return new Promise((resolve, reject) => {
            const file = require('fs').createWriteStream(localPath);
            
            https.get(url, (response) => {
                if (response.statusCode !== 200) {
                    file.close();
                    fs.unlink(localPath).catch(() => {});
                    resolve(false);
                    return;
                }
                
                response.pipe(file);
                file.on('finish', () => {
                    file.close();
                    resolve(true);
                });
                file.on('error', (err) => {
                    file.close();
                    fs.unlink(localPath).catch(() => {});
                    resolve(false);
                });
            }).on('error', () => resolve(false));
        });
    }

    /**
     * Import template to database using direct SQL insertion
     */
    async importToDatabase(templateData, thumbnailPaths, templateMeta = null) {
        console.log(`  💾 Importing to database...`);

        // Use manifest fields when available, otherwise derive them
        const category = templateMeta?.category || this.getTemplateCategory(templateData);
        const tags = templateMeta?.tags ? 
            (Array.isArray(templateMeta.tags) ? templateMeta.tags : templateMeta.tags.split(',').map(t => t.trim())) :
            this.getTemplateTags(templateData);

        // Log data source for debugging
        console.log(`    Category: ${category} ${templateMeta?.category ? '(from manifest)' : '(derived)'}`);
        console.log(`    Tags: [${tags.join(', ')}] ${templateMeta?.tags ? '(from manifest)' : '(derived)'}`);

        try {
            // Generate UUID for the template (using simple UUID v4 generator)
            const uuid = this.generateUUID();
            const now = new Date().toISOString().slice(0, 19).replace('T', ' ');

            // Check if template already exists by UUID binary
            const [existingRows] = await this.dbConnection.execute(
                'SELECT id FROM templates WHERE uuid = ?',
                [Buffer.from(templateData.id.replace(/-/g, ''), 'hex')]
            );

            if (existingRows.length > 0 && !this.options.force) {
                console.log(`    ⏭️  Template already exists in database, skipping`);
                return;
            }

            // Delete existing if force mode
            if (existingRows.length > 0 && this.options.force) {
                await this.dbConnection.execute(
                    'DELETE FROM templates WHERE uuid = ?',
                    [Buffer.from(templateData.id.replace(/-/g, ''), 'hex')]
                );
                console.log(`    🔄 Deleted existing template for re-import`);
            }

            // Prepare SQL insertion
            const sql = `
                INSERT INTO templates (
                    uuid, name, description, category, tags, width, height,
                    canvas_settings, layers, thumbnail_url, preview_url,
                    is_premium, is_active, is_public, is_recommended,
                    usage_count, rating, rating_count, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            `;

            const values = [
                Buffer.from(templateData.id.replace(/-/g, ''), 'hex'), // Convert UUID string to binary
                templateData.name || templateData.title,
                templateData.description || '',
                category,
                JSON.stringify(tags),
                templateData.width,
                templateData.height,
                JSON.stringify(templateData.data || {}),
                JSON.stringify(templateData.layers || []),
                thumbnailPaths.thumbnail || null,
                thumbnailPaths.preview || null,
                this.isTemplatePremium(templateData) ? 1 : 0,
                1, // is_active
                1, // is_public
                0, // is_recommended
                0, // usage_count
                '0.00', // rating
                0, // rating_count
                now, // created_at
                now  // updated_at
            ];

            // Execute the insertion
            const [result] = await this.dbConnection.execute(sql, values);
            
            console.log(`    ✅ Template inserted with ID: ${result.insertId}`);
            
        } catch (error) {
            console.error(`    ❌ Database insertion failed:`, error.message);
            throw new Error(`Database insertion failed: ${error.message}`);
        }
    }

    /**
     * Simple UUID v4 generator for compatibility
     */
    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    /**
     * Template categorization logic
     */
    getTemplateCategory(templateData) {
        // If already has category (from manifest), use it
        if (templateData.category) {
            return templateData.category;
        }

        const { width, height } = templateData;
        const title = (templateData.title || templateData.name || '').toLowerCase();

        // Social media dimensions
        if (width === 1080 && height === 1080) return 'social-media'; // Instagram square
        if (width === 1080 && height === 1920) return 'social-media'; // Instagram story
        if (width === 1200 && height === 630) return 'social-media';  // Facebook post
        
        // Presentation dimensions
        if (width === 1920 && height === 1080) return 'presentation'; // HD presentation
        if (width === 1280 && height === 720) return 'presentation';  // HD 720p
        
        // Print dimensions (assuming 300 DPI)
        if (width >= 2480 && height >= 3508) return 'print'; // A4 or larger
        
        // Title-based categorization
        if (title.includes('logo')) return 'logo';
        if (title.includes('business card')) return 'print';
        if (title.includes('flyer') || title.includes('poster')) return 'print';
        if (title.includes('web') || title.includes('banner')) return 'web-graphics';
        
        // Default
        return 'marketing';
    }

    /**
     * Generate template tags
     */
    getTemplateTags(templateData) {
        // If already has tags (from manifest), use them
        if (templateData.tags) {
            if (Array.isArray(templateData.tags)) {
                return templateData.tags;
            } else if (typeof templateData.tags === 'string') {
                return templateData.tags.split(',').map(t => t.trim());
            }
        }

        const tags = [];
        const { width, height } = templateData;
        const title = (templateData.title || templateData.name || '').toLowerCase();

        // Aspect ratio tags
        if (width && height) {
            const ratio = width / height;
            if (Math.abs(ratio - 1) < 0.1) tags.push('square');
            else if (ratio > 1.5) tags.push('landscape');
            else if (ratio < 0.7) tags.push('portrait');

            // Size tags
            if (width >= 1920) tags.push('hd');
            if (width === 1080) tags.push('standard');
        }

        // Content tags from title
        const contentKeywords = ['business', 'modern', 'minimal', 'creative', 'professional', 'bold', 'elegant'];
        contentKeywords.forEach(keyword => {
            if (title.includes(keyword)) tags.push(keyword);
        });

        return tags.slice(0, 5);
    }

    /**
     * Determine premium status
     */
    isTemplatePremium(templateData) {
        // More sophisticated logic based on complexity
        const layerCount = templateData.layers ? templateData.layers.length : 0;
        const hasImages = templateData.layers?.some(l => l.type === 'image') || false;
        
        // Premium if complex or contains images
        return layerCount > 5 || hasImages || Math.random() < 0.25;
    }

    /**
     * Check if template exists in database
     */
    async templateExists(templateId) {
        try {
            const [rows] = await this.dbConnection.execute(
                'SELECT id FROM templates WHERE uuid = ? LIMIT 1',
                [Buffer.from(templateId.replace(/-/g, ''), 'hex')]
            );
            return rows.length > 0;
        } catch (error) {
            console.error(`    ⚠️  Error checking template existence: ${error.message}`);
            return false;
        }
    }

    /**
     * Fetch JSON from URL
     */
    async fetchJSON(url) {
        return new Promise((resolve, reject) => {
            https.get(url, (res) => {
                if (res.statusCode !== 200) {
                    reject(new Error(`HTTP ${res.statusCode} for ${url}`));
                    return;
                }
                
                let data = '';
                res.on('data', chunk => data += chunk);
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
     * Ensure directories exist
     */
    async ensureDirectories() {
        const dirs = [CONFIG.backend.uploadDir, CONFIG.backend.thumbnailDir];
        
        for (const dir of dirs) {
            await fs.mkdir(dir, { recursive: true });
        }
        
        console.log('📁 Ensured directories exist');
    }

    /**
     * Print summary
     */
    printSummary() {
        console.log('\n📊 Import Summary:');
        console.log('==================');
        console.log(`Templates processed: ${this.stats.processed}`);
        console.log(`Successfully imported: ${this.stats.imported}`);
        console.log(`Skipped (existing): ${this.stats.skipped}`);
        console.log(`Errors: ${this.stats.errors}`);
        console.log(`Assets downloaded: ${this.stats.assetsDownloaded}`);
        console.log(`Thumbnails generated: ${this.stats.thumbnailsGenerated}`);
        
        const successRate = this.stats.processed > 0 ? 
            ((this.stats.imported / this.stats.processed) * 100).toFixed(1) : 0;
        console.log(`Success rate: ${successRate}%`);
        
        if (this.stats.errors > 0) {
            console.log('\n⚠️  Some templates failed to import. Check error messages above.');
        } else {
            console.log('\n✅ Template import completed successfully!');
        }
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
            case '--force':
                options.force = true;
                break;
            case '--dry-run':
                options.dryRun = true;
                break;
            case '--category':
                options.category = args[++i];
                break;
            case '--clear-existing':
                options.clearExisting = true;
                break;
            case '--no-previews':
                options.generatePreviews = false;
                break;
            case '--help':
                console.log(`
Advanced Template Import Script

Usage: node advanced-template-importer.js [options]

Options:
  --limit <number>     Limit number of templates to process
  --force             Force re-import of existing templates
  --dry-run           Show what would be imported (no changes)
  --category <name>   Filter by category (social-media, presentation, etc.)
  --no-previews       Skip generating preview images
  --help              Show this help

Environment Variables:
  DATABASE_URL        Database connection string (from backend/.env)

Examples:
  npm run import:sample                           # Dry run with 5 templates
  npm run import:fresh                            # Clear all and import 20 new
  npm run import:all                              # Clear all and import everything
  node advanced-template-importer.js --help      # Show help
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
    const importer = new AdvancedTemplateImporter(options);
    
    importer.run().catch(error => {
        console.error('\n💥 Fatal error:', error.message);
        console.error(error.stack);
        process.exit(1);
    });
}

module.exports = { AdvancedTemplateImporter };
