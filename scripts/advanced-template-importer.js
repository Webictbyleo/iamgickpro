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
 *   --backend-dir <path>  Specify custom backend directory path
 *   --no-previews         Skip generating preview images
 *   --help                Show help
 * 
 * Environment Variables:
 *   DATABASE_URL          Database connection string (must be provided externally)
 *   BACKEND_DIR           Backend directory path (alternative to --backend-dir)
 * 
 * Examples:
 *   npm run import:sample                           # Dry run with 5 templates
 *   npm run import:fresh                            # Clear all and import 20 new
 *   npm run import:all                              # Clear all and import everything
 *   node advanced-template-importer.js --help      # Show help
 */

const path = require('path');
const https = require('https');
const http = require('http');
const fs = require('fs').promises;
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
        uploadDir: path.resolve(__dirname, '../backend/public/uploads/templates'),
        thumbnailDir: path.resolve(__dirname, '../backend/public/uploads/thumbnails')
    },
    frontend: {
        srcDir: path.resolve(__dirname, '../frontend/src'),
        buildDir: path.resolve(__dirname, '../frontend/dist')
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
            backendDir: options.backendDir || process.env.BACKEND_DIR || path.resolve(__dirname, '../backend'),
            ...options
        };
        
        // Update CONFIG with the specified backend directory
        this.config = {
            ...CONFIG,
            backend: {
                ...CONFIG.backend,
                uploadDir: path.resolve(this.options.backendDir, 'public/uploads/templates'),
                thumbnailDir: path.resolve(this.options.backendDir, 'public/uploads/thumbnails')
            }
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
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                    return this;
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
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                    return this;
                }
                
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
                    this._image = null;
                    this._imageLoaded = false;
                    
                    // If image source is provided, load it
                    if (config.image) {
                        this.image(config.image);
                    }
                }
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                    if (attrs.image) {
                        this.image(attrs.image);
                    }
                }
                
                destroy() {
                    this._image = null;
                    this._imageLoaded = false;
                }
                
                image(imgElement) {
                    if (imgElement !== undefined) {
                        this._image = imgElement;
                        this._imageLoaded = true;
                        return this;
                    }
                    return this._image;
                }
                
                async loadImageFromSrc(src) {
                    return new Promise((resolve, reject) => {
                        const { loadImage } = require('canvas');
                        
                        // Convert relative paths to absolute file paths
                        let imagePath = src;
                        if (src.startsWith('/uploads/templates/')) {
                            // Convert to absolute path
                            imagePath = path.join(this.options.backendDir, 'public', src);
                        } else if (src.startsWith('/converted_assets/')) {
                            // This should have been converted already, but handle fallback
                            const filename = path.basename(src);
                            imagePath = path.join(this.options.backendDir, 'public', 'uploads', 'templates', filename);
                        }
                        
                        loadImage(imagePath)
                            .then(image => {
                                this._image = image;
                                this._imageLoaded = true;
                                resolve(image);
                            })
                            .catch(error => {
                                console.log(`    ⚠️  Failed to load image ${imagePath}: ${error.message}`);
                                this._image = null;
                                this._imageLoaded = false;
                                resolve(null); // Don't reject, just continue without image
                            });
                    });
                }
                
                render(ctx) {
                    ctx.save();
                    ctx.globalAlpha = this.opacity || 1;
                    
                    const x = typeof this.x === 'function' ? this.x() : (this.x || 0);
                    const y = typeof this.y === 'function' ? this.y() : (this.y || 0);
                    const width = typeof this.width === 'function' ? this.width() : (this.width || 100);
                    const height = typeof this.height === 'function' ? this.height() : (this.height || 100);
                    
                    if (this._image && this._imageLoaded) {
                        try {
                            ctx.drawImage(this._image, x, y, width, height);
                        } catch (error) {
                            // Fallback to placeholder if image rendering fails
                            this.renderPlaceholder(ctx, x, y, width, height);
                        }
                    } else {
                        // Placeholder for missing images
                        this.renderPlaceholder(ctx, x, y, width, height);
                    }
                    ctx.restore();
                }
                
                renderPlaceholder(ctx, x, y, width, height) {
                    // Draw a subtle placeholder
                    ctx.fillStyle = '#f8f9fa';
                    ctx.fillRect(x, y, width, height);
                    
                    // Add a border
                    ctx.strokeStyle = '#dee2e6';
                    ctx.lineWidth = 1;
                    ctx.strokeRect(x, y, width, height);
                    
                    // Add an icon-like shape in center
                    const centerX = x + width / 2;
                    const centerY = y + height / 2;
                    const iconSize = Math.min(width, height) * 0.2;
                    
                    ctx.fillStyle = '#adb5bd';
                    ctx.fillRect(centerX - iconSize/2, centerY - iconSize/2, iconSize, iconSize);
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
                        return this.children.find(child => {
                            // Check constructor name (e.g., 'MockImage' matches 'Image')
                            if (child.constructor.name.includes(selector)) {
                                return true;
                            }
                            // Check by class name
                            if (child.constructor.name === `Mock${selector}`) {
                                return true;
                            }
                            // Check tagName if it exists
                            if (child.tagName === selector) {
                                return true;
                            }
                            return false;
                        });
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
            },
            
            Circle: class MockCircle {
                constructor(config = {}) {
                    Object.assign(this, {
                        x: 0,
                        y: 0,
                        radius: 50,
                        fill: '#cccccc',
                        opacity: 1,
                        ...config
                    });
                }
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                    return this;
                }
                
                destroy() {}
                
                render(ctx) {
                    ctx.save();
                    ctx.globalAlpha = this.opacity || 1;
                    ctx.fillStyle = this.fill || '#cccccc';
                    
                    const x = typeof this.x === 'function' ? this.x() : (this.x || 0);
                    const y = typeof this.y === 'function' ? this.y() : (this.y || 0);
                    const radius = typeof this.radius === 'function' ? this.radius() : (this.radius || 50);
                    
                    ctx.beginPath();
                    ctx.arc(x, y, radius, 0, 2 * Math.PI);
                    ctx.fill();
                    ctx.restore();
                }
            },
            
            Ellipse: class MockEllipse {
                constructor(config = {}) {
                    Object.assign(this, {
                        x: 0,
                        y: 0,
                        radiusX: 50,
                        radiusY: 30,
                        fill: '#cccccc',
                        opacity: 1,
                        ...config
                    });
                }
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                    return this;
                }
                
                destroy() {}
                
                render(ctx) {
                    ctx.save();
                    ctx.globalAlpha = this.opacity || 1;
                    ctx.fillStyle = this.fill || '#cccccc';
                    
                    const x = typeof this.x === 'function' ? this.x() : (this.x || 0);
                    const y = typeof this.y === 'function' ? this.y() : (this.y || 0);
                    const radiusX = typeof this.radiusX === 'function' ? this.radiusX() : (this.radiusX || 50);
                    const radiusY = typeof this.radiusY === 'function' ? this.radiusY() : (this.radiusY || 30);
                    
                    ctx.beginPath();
                    ctx.ellipse(x, y, radiusX, radiusY, 0, 0, 2 * Math.PI);
                    ctx.fill();
                    ctx.restore();
                }
            },
            
            RegularPolygon: class MockRegularPolygon {
                constructor(config = {}) {
                    Object.assign(this, {
                        x: 0,
                        y: 0,
                        sides: 6,
                        radius: 50,
                        fill: '#cccccc',
                        opacity: 1,
                        ...config
                    });
                }
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                    return this;
                }
                
                destroy() {}
                
                render(ctx) {
                    ctx.save();
                    ctx.globalAlpha = this.opacity || 1;
                    ctx.fillStyle = this.fill || '#cccccc';
                    
                    const x = typeof this.x === 'function' ? this.x() : (this.x || 0);
                    const y = typeof this.y === 'function' ? this.y() : (this.y || 0);
                    const sides = typeof this.sides === 'function' ? this.sides() : (this.sides || 6);
                    const radius = typeof this.radius === 'function' ? this.radius() : (this.radius || 50);
                    
                    ctx.beginPath();
                    for (let i = 0; i < sides; i++) {
                        const angle = (i * 2 * Math.PI) / sides;
                        const pointX = x + radius * Math.cos(angle);
                        const pointY = y + radius * Math.sin(angle);
                        if (i === 0) {
                            ctx.moveTo(pointX, pointY);
                        } else {
                            ctx.lineTo(pointX, pointY);
                        }
                    }
                    ctx.closePath();
                    ctx.fill();
                    ctx.restore();
                }
            },
            
            Star: class MockStar {
                constructor(config = {}) {
                    Object.assign(this, {
                        x: 0,
                        y: 0,
                        numPoints: 5,
                        innerRadius: 25,
                        outerRadius: 50,
                        fill: '#cccccc',
                        opacity: 1,
                        ...config
                    });
                }
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                    return this;
                }
                
                destroy() {}
                
                render(ctx) {
                    ctx.save();
                    ctx.globalAlpha = this.opacity || 1;
                    ctx.fillStyle = this.fill || '#cccccc';
                    
                    const x = typeof this.x === 'function' ? this.x() : (this.x || 0);
                    const y = typeof this.y === 'function' ? this.y() : (this.y || 0);
                    const numPoints = typeof this.numPoints === 'function' ? this.numPoints() : (this.numPoints || 5);
                    const innerRadius = typeof this.innerRadius === 'function' ? this.innerRadius() : (this.innerRadius || 25);
                    const outerRadius = typeof this.outerRadius === 'function' ? this.outerRadius() : (this.outerRadius || 50);
                    
                    ctx.beginPath();
                    for (let i = 0; i < numPoints * 2; i++) {
                        const angle = (i * Math.PI) / numPoints;
                        const radius = i % 2 === 0 ? outerRadius : innerRadius;
                        const pointX = x + radius * Math.cos(angle);
                        const pointY = y + radius * Math.sin(angle);
                        if (i === 0) {
                            ctx.moveTo(pointX, pointY);
                        } else {
                            ctx.lineTo(pointX, pointY);
                        }
                    }
                    ctx.closePath();
                    ctx.fill();
                    ctx.restore();
                }
            },
            
            Line: class MockLine {
                constructor(config = {}) {
                    Object.assign(this, {
                        points: [0, 0, 100, 100],
                        stroke: '#000000',
                        strokeWidth: 2,
                        opacity: 1,
                        ...config
                    });
                }
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                    return this;
                }
                
                destroy() {}
                
                render(ctx) {
                    ctx.save();
                    ctx.globalAlpha = this.opacity || 1;
                    ctx.strokeStyle = this.stroke || '#000000';
                    ctx.lineWidth = this.strokeWidth || 2;
                    
                    const points = typeof this.points === 'function' ? this.points() : (this.points || [0, 0, 100, 100]);
                    
                    ctx.beginPath();
                    for (let i = 0; i < points.length; i += 2) {
                        if (i === 0) {
                            ctx.moveTo(points[i], points[i + 1]);
                        } else {
                            ctx.lineTo(points[i], points[i + 1]);
                        }
                    }
                    ctx.stroke();
                    ctx.restore();
                }
            },
            
            Path: class MockPath {
                constructor(config = {}) {
                    Object.assign(this, {
                        data: '',
                        fill: '#cccccc',
                        stroke: '#000000',
                        strokeWidth: 1,
                        opacity: 1,
                        ...config
                    });
                }
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                    return this;
                }
                
                destroy() {}
                
                render(ctx) {
                    ctx.save();
                    ctx.globalAlpha = this.opacity || 1;
                    
                    // Simple fallback for path rendering
                    if (this.fill) {
                        ctx.fillStyle = this.fill;
                        ctx.fillRect(0, 0, 50, 50); // Simple placeholder
                    }
                    if (this.stroke) {
                        ctx.strokeStyle = this.stroke;
                        ctx.lineWidth = this.strokeWidth || 1;
                        ctx.strokeRect(0, 0, 50, 50); // Simple placeholder
                    }
                    
                    ctx.restore();
                }
            },
            
            Shape: class MockShape {
                constructor(config = {}) {
                    Object.assign(this, {
                        x: 0,
                        y: 0,
                        width: 100,
                        height: 100,
                        fill: '#cccccc',
                        opacity: 1,
                        ...config
                    });
                }
                
                setAttrs(attrs) {
                    Object.assign(this, attrs);
                    return this;
                }
                
                destroy() {}
                
                render(ctx) {
                    ctx.save();
                    ctx.globalAlpha = this.opacity || 1;
                    ctx.fillStyle = this.fill || '#cccccc';
                    
                    const x = typeof this.x === 'function' ? this.x() : (this.x || 0);
                    const y = typeof this.y === 'function' ? this.y() : (this.y || 0);
                    const width = typeof this.width === 'function' ? this.width() : (this.width || 100);
                    const height = typeof this.height === 'function' ? this.height() : (this.height || 100);
                    
                    ctx.fillRect(x, y, width, height);
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
            const rendererPath = path.join(CONFIG.frontend.srcDir, 'editor/sdk/DesignRenderer.ts');
            
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
            console.log(`   ❌ Failed to load DesignRenderer: ${error.message}`);
            throw error; // Don't fall back, we want to fix the real issue
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
                    for (const layer of design.layers) {
                        // Get position and size from transform object (new format) or direct properties (legacy)
                        const x = layer.transform?.x || layer.x || 0;
                        const y = layer.transform?.y || layer.y || 0;
                        const width = layer.transform?.width || layer.width || 100;
                        const height = layer.transform?.height || layer.height || 100;
                        const opacity = layer.transform?.opacity || layer.opacity || 1;
                        
                        if (layer.type === 'text') {
                            ctx.save();
                            ctx.globalAlpha = opacity;
                            ctx.fillStyle = layer.properties?.color || '#000000';
                            ctx.font = `${(layer.properties?.fontSize || 16) * scale}px ${layer.properties?.fontFamily || 'Arial'}`;
                            ctx.fillText(
                                layer.properties?.text || 'Sample Text',
                                x * scale,
                                y * scale
                            );
                            ctx.restore();
                        } else if (layer.type === 'shape') {
                            ctx.save();
                            ctx.globalAlpha = opacity;
                            ctx.fillStyle = layer.properties?.fill || '#cccccc';
                            ctx.fillRect(
                                x * scale,
                                y * scale,
                                width * scale,
                                height * scale
                            );
                            ctx.restore();
                        } else if (layer.type === 'image' && layer.properties?.src) {
                            try {
                                // Load and render the image
                                const { loadImage } = require('canvas');
                                
                                // Convert relative paths to absolute file paths
                                let imagePath = layer.properties.src;
                                if (imagePath.startsWith('/uploads/templates/')) {
                                    imagePath = path.join(this.options.backendDir, 'public', imagePath);
                                } else if (imagePath.startsWith('/converted_assets/')) {
                                    const filename = path.basename(imagePath);
                                    imagePath = path.join(this.options.backendDir, 'public', 'uploads', 'templates', filename);
                                }
                                
                                console.log(`    🖼️  Loading image: ${imagePath}`);
                                const img = await loadImage(imagePath);
                                
                                ctx.save();
                                ctx.globalAlpha = opacity;
                                ctx.drawImage(
                                    img,
                                    x * scale,
                                    y * scale,
                                    width * scale,
                                    height * scale
                                );
                                ctx.restore();
                                console.log(`    ✅ Image rendered: ${width}x${height} at ${x},${y}`);
                            } catch (error) {
                                console.log(`    ⚠️  Failed to load image ${layer.properties.src}: ${error.message}`);
                                // Fallback placeholder for failed image loads
                                ctx.save();
                                ctx.globalAlpha = opacity;
                                ctx.fillStyle = '#f8f9fa';
                                ctx.fillRect(
                                    x * scale,
                                    y * scale,
                                    width * scale,
                                    height * scale
                                );
                                ctx.strokeStyle = '#dee2e6';
                                ctx.lineWidth = 1;
                                ctx.strokeRect(
                                    x * scale,
                                    y * scale,
                                    width * scale,
                                    height * scale
                                );
                                
                                // Add "IMG" text in center
                                ctx.fillStyle = '#adb5bd';
                                ctx.font = '12px Arial';
                                ctx.textAlign = 'center';
                                ctx.fillText(
                                    'IMG',
                                    (x + width/2) * scale,
                                    (y + height/2) * scale
                                );
                                ctx.restore();
                                console.log(`    📷 Placeholder rendered: ${width}x${height} at ${x},${y}`);
                            }
                        }
                    }
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
        
        // Replace renderer instantiations with our context versions
        source = source.replace(/new TextLayerRenderer\(\)/g, 'new TextLayerRenderer()');
        source = source.replace(/new ImageLayerRenderer\(\)/g, 'new ImageLayerRenderer()');
        source = source.replace(/new ShapeLayerRenderer\(\)/g, 'new ShapeLayerRenderer()');
        source = source.replace(/new SVGLayerRenderer\(\)/g, 'new SVGLayerRenderer()');
        source = source.replace(/new GroupLayerRenderer\(\)/g, 'new GroupLayerRenderer()');
        
        // The renderer classes will be available as global variables in the VM context
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
                // Get position and size from transform object (new format) or direct properties (legacy)
                const x = layerNode.transform?.x || layerNode.x || 0;
                const y = layerNode.transform?.y || layerNode.y || 0;
                const width = layerNode.transform?.width || layerNode.width || 100;
                const height = layerNode.transform?.height || layerNode.height || 100;
                const opacity = layerNode.transform?.opacity || layerNode.opacity || 1;
                
                const text = new global.Konva.Text({
                    x: x,
                    y: y,
                    text: layerNode.properties?.text || 'Text',
                    fontSize: layerNode.properties?.fontSize || 16,
                    fontFamily: layerNode.properties?.fontFamily || 'Arial',
                    fill: layerNode.properties?.color || '#000000',
                    width: width,
                    height: height,
                    opacity: opacity
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
            async render(layerNode) {
                // Get position and size from transform object (new format) or direct properties (legacy)
                const x = layerNode.transform?.x || layerNode.x || 0;
                const y = layerNode.transform?.y || layerNode.y || 0;
                const width = layerNode.transform?.width || layerNode.width || 100;
                const height = layerNode.transform?.height || layerNode.height || 100;
                const opacity = layerNode.transform?.opacity || layerNode.opacity || 1;
                
                // Create a group like the frontend renderer does
                const group = new global.Konva.Group({
                    id: layerNode.id.toString(),
                    x: x,
                    y: y,
                    width: width,
                    height: height,
                    opacity: opacity
                });
                
                // Create an image node within the group
                const image = new global.Konva.Image({
                    x: 0, // Relative to group
                    y: 0, // Relative to group
                    width: width,
                    height: height,
                    opacity: 1
                });
                
                // Load the actual image if src is provided
                if (layerNode.properties?.src) {
                    try {
                        await image.loadImageFromSrc(layerNode.properties.src);
                    } catch (error) {
                        console.log(`    ⚠️  ImageLayerRenderer: Failed to load image for layer: ${error.message}`);
                    }
                }
                
                group.add(image);
                return group;
            }
            
            async update(node, layerNode) {
                if (node && typeof node.setAttrs === 'function') {
                    // Get position and size from transform object (new format) or direct properties (legacy)
                    const width = layerNode.transform?.width || layerNode.width || 100;
                    const height = layerNode.transform?.height || layerNode.height || 100;
                    
                    node.setAttrs({
                        width: width,
                        height: height
                    });
                    
                    // Find the image node within the group and update it
                    if (typeof node.findOne === 'function') {
                        const imageNode = node.findOne('Image');
                        if (imageNode && layerNode.properties?.src) {
                            try {
                                await imageNode.loadImageFromSrc(layerNode.properties.src);
                            } catch (error) {
                                console.log(`    ⚠️  ImageLayerRenderer: Failed to update image: ${error.message}`);
                            }
                        }
                    }
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
                // Get position and size from transform object (new format) or direct properties (legacy)
                const x = layerNode.transform?.x || layerNode.x || 0;
                const y = layerNode.transform?.y || layerNode.y || 0;
                const width = layerNode.transform?.width || layerNode.width || 100;
                const height = layerNode.transform?.height || layerNode.height || 100;
                const opacity = layerNode.transform?.opacity || layerNode.opacity || 1;
                
                const rect = new global.Konva.Rect({
                    x: x,
                    y: y,
                    width: width,
                    height: height,
                    fill: layerNode.properties?.fill || '#cccccc',
                    opacity: opacity
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
                // Get position and size from transform object (new format) or direct properties (legacy)
                const x = layerNode.transform?.x || layerNode.x || 0;
                const y = layerNode.transform?.y || layerNode.y || 0;
                const opacity = layerNode.transform?.opacity || layerNode.opacity || 1;
                
                const group = new global.Konva.Group({
                    x: x,
                    y: y,
                    opacity: opacity
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
                // Get position and size from transform object (new format) or direct properties (legacy)
                const x = layerNode.transform?.x || layerNode.x || 0;
                const y = layerNode.transform?.y || layerNode.y || 0;
                const width = layerNode.transform?.width || layerNode.width || 100;
                const height = layerNode.transform?.height || layerNode.height || 100;
                const opacity = layerNode.transform?.opacity || layerNode.opacity || 1;
                
                // Fallback to shape renderer for SVG
                const rect = new global.Konva.Rect({
                    x: x,
                    y: y,
                    width: width,
                    height: height,
                    fill: layerNode.properties?.fill || '#cccccc',
                    opacity: opacity
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
            
            // Mock renderer classes - accessible as this.RendererName
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

            // Recalculate text dimensions before generating thumbnails or saving
            await this.recalculateTextLayerDimensions(templateData);

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

        // Use absolute paths from this.config (which includes custom backend directory)
        const thumbnailDir = this.config.backend.thumbnailDir;
        const thumbnailPath = path.join(thumbnailDir, `${templateData.id}.png`);
        const previewPath = path.join(thumbnailDir, `${templateData.id}_preview.png`);

        // Ensure the thumbnail directory exists
        await fs.mkdir(thumbnailDir, { recursive: true });

        // Check if thumbnails already exist and show appropriate message
        try {
            await fs.access(thumbnailPath);
            if (this.options.force) {
                console.log(`    🔄 Thumbnail exists but force mode enabled, regenerating...`);
            } else {
                console.log(`    📁 Thumbnail already exists, regenerating anyway...`);
            }
        } catch {
            console.log(`    ✨ Creating new thumbnail...`);
        }

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
            console.log(`    ✅ Thumbnail generated: ${CONFIG.defaults.thumbnailWidth}x${CONFIG.defaults.thumbnailHeight}`);

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
                previewRelativePath = true; // Mark that preview was generated
                console.log(`    ✅ Preview generated: ${CONFIG.defaults.previewWidth}x${CONFIG.defaults.previewHeight}`);
            }

            this.stats.thumbnailsGenerated++;

            // Calculate relative paths from the backend public directory for serving
            const backendPublicDir = path.resolve(this.options.backendDir, 'public');
            
            // Ensure paths start with forward slash for proper URL serving
            const thumbnailRelativePath = path.relative(backendPublicDir, thumbnailPath);
            const previewRelativePath_url = previewRelativePath ? path.relative(backendPublicDir, previewPath) : null;
            
            return {
                thumbnail: '/' + thumbnailRelativePath.replace(/\\/g, '/'),
                preview: previewRelativePath_url ? '/' + previewRelativePath_url.replace(/\\/g, '/') : null
            };

        } catch (error) {
            throw new Error(`Thumbnail generation failed: ${error.message}`);
        }
    }

    /**
     * Download template assets and update paths
     */
    async downloadTemplateAssets(templateData) {
        if (!templateData.layers) return;

        console.log(`  📦 Downloading assets and updating paths...`);
        let downloadCount = 0;
        let updatedCount = 0;

        for (const layer of templateData.layers) {
            if (layer.type === 'image' && layer.properties?.src) {
                const originalPath = layer.properties.src;
                const { downloaded, newPath } = await this.downloadAsset(originalPath);
                
                if (downloaded) downloadCount++;
                
                if (newPath) {
                    // Update the layer's src path to the new accessible URL
                    layer.properties.src = this.config.backend.uploadDir + newPath;
                    updatedCount++;
                    console.log(`    🔗 Updated path: ${originalPath} → ${newPath}`);
                }
            }
        }

        if (downloadCount > 0) {
            console.log(`    ✅ Downloaded ${downloadCount} assets`);
            this.stats.assetsDownloaded += downloadCount;
        }
        
        if (updatedCount > 0) {
            console.log(`    🔄 Updated ${updatedCount} asset paths`);
        }
    }

    /**
     * Download a single asset and return new path
     */
    async downloadAsset(assetPath) {
        if (!assetPath.startsWith('/converted_assets/')) {
            return { downloaded: false, newPath: null };
        }

        const filename = path.basename(assetPath);
        const url = `${CONFIG.github.repo}/converted_assets/${filename}`;
        
        // Store assets in the uploads/templates directory accessible via HTTP
        // Use this.config instead of CONFIG to get the correct backend directory
        const localPath = path.join(this.config.backend.uploadDir, filename);
        
        // Create the new URL path that the frontend can access
        // This should be relative to the backend's public directory
        const newPath = `/uploads/templates/${filename}`;

        // Check if file already exists
        try {
            await fs.access(localPath);
            if (!this.options.force) {
                console.log(`    📁 Asset already exists: ${filename}`);
                return { downloaded: false, newPath };
            } else {
                console.log(`    🔄 Asset exists but force mode enabled, re-downloading: ${filename}`);
            }
        } catch {}

        console.log(`    📥 Downloading: ${filename}`);

        // Download the asset
        return new Promise((resolve, reject) => {
            const file = require('fs').createWriteStream(localPath);
            
            https.get(url, (response) => {
                if (response.statusCode !== 200) {
                    console.log(`    ❌ Failed to download ${filename}: HTTP ${response.statusCode}`);
                    file.close();
                    fs.unlink(localPath).catch(() => {});
                    resolve({ downloaded: false, newPath: null });
                    return;
                }
                
                response.pipe(file);
                file.on('finish', () => {
                    file.close();
                    console.log(`    ✅ Downloaded: ${filename}`);
                    resolve({ downloaded: true, newPath });
                });
                file.on('error', (err) => {
                    console.log(`    ❌ File write error for ${filename}:`, err.message);
                    file.close();
                    fs.unlink(localPath).catch(() => {});
                    resolve({ downloaded: false, newPath: null });
                });
            }).on('error', (err) => {
                console.log(`    ❌ Network error for ${filename}:`, err.message);
                resolve({ downloaded: false, newPath: null });
            });
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
     * Recalculates width and height for text layers based on their content,
     * accounting for multiple lines. This ensures the saved dimensions match
     * the actual rendered size of the text.
     */
    async recalculateTextLayerDimensions(templateData) {
        if (!templateData.layers || !Array.isArray(templateData.layers)) {
            return;
        }

        console.log('  📏 Recalculating dimensions for text layers...');

        // A single canvas instance can be reused for all measurements
        const canvas = createCanvas(1, 1);
        const ctx = canvas.getContext('2d');
        let updatedCount = 0;

        for (const layer of templateData.layers) {
            // Process only text layers that have text content
            if (layer.type === 'text' && layer.properties?.text) {
                const text = String(layer.properties.text);
                const textLines = text.split('\n');
                const fontSize = layer.properties.fontSize || 16;
                const fontFamily = layer.properties.fontFamily || 'Arial';
                // Konva's default lineHeight is a multiplier of the font size. Let's assume a default if not present.
                const lineHeightMultiplier = layer.properties.lineHeight || 1.2;

                // Set the font on the context to get accurate measurements
                ctx.font = `${fontSize}px "${fontFamily}"`;

                // Find the width of the longest line
                let maxWidth = 0;
                for (const line of textLines) {
                    const metrics = ctx.measureText(line);
                    if (metrics.width > maxWidth) {
                        maxWidth = metrics.width;
                    }
                }
                
                // Calculate the total height based on number of lines, font size, and line height
                // This mimics how Konva calculates text block height
                const calculatedHeight = textLines.length * fontSize * lineHeightMultiplier;
                const calculatedWidth = maxWidth;

                // Only update if the dimensions are different, to avoid unnecessary logs
                if (Math.abs((layer.width || 0) - calculatedWidth) > 0.01 || Math.abs((layer.height || 0) - calculatedHeight) > 0.01) {
                    console.log(`    - Layer ID ${layer.id}: '${text.substring(0, 30).replace(/\n/g, '\\n')}...'`);
                    console.log(`      Original dims: ${layer.width?.toFixed(2) || 'N/A'}x${layer.height?.toFixed(2) || 'N/A'}`);
                    console.log(`      Calculated dims: ${calculatedWidth.toFixed(2)}x${calculatedHeight.toFixed(2)}`);

                    // Update the layer's width and height. This will be persisted to the DB.
                    layer.width = calculatedWidth;
                    layer.height = calculatedHeight;
                    
                    // Also update the transform object if it exists, for consistency
                    if (layer.transform) {
                        layer.transform.width = calculatedWidth;
                        layer.transform.height = calculatedHeight;
                    }

                    updatedCount++;
                }
            }
        }

        if (updatedCount > 0) {
            console.log(`    ✅ Updated dimensions for ${updatedCount} text layers.`);
        } else {
            console.log('    ✅ No text layers found or their dimensions were already correct.');
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
        const dirs = [this.config.backend.uploadDir, this.config.backend.thumbnailDir];
        
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
            case '--backend-dir':
                options.backendDir = args[++i];
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
  DATABASE_URL        Database connection string (must be provided externally)
  BACKEND_DIR         Backend directory path (alternative to --backend-dir)

Examples:
  npm run import:sample                           # Dry run with 5 templates
  npm run import:fresh                            # Clear all and import 20 new
  npm run import:all                              # Clear all and import everything
  node advanced-template-importer.js --help      # Show help
                `);
                process.exit(0);
            default:
                if (args[i].startsWith('--backend-dir=')) {
                    // Handle --backend-dir=/path/to/backend format
                    options.backendDir = args[i].substring('--backend-dir='.length);
                } else if (args[i].startsWith('--')) {
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