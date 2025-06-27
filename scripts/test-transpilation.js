#!/usr/bin/env node

const { AdvancedTemplateImporter } = require('./advanced-template-importer.js');
const fs = require('fs').promises;
const path = require('path');

async function testTranspilation() {
    console.log('Testing TypeScript transpilation...');
    
    try {
        const importer = new AdvancedTemplateImporter();
        const rendererPath = path.join(__dirname, '../frontend/src/editor/sdk/DesignRenderer.ts');
        
        console.log('Reading DesignRenderer.ts...');
        const tsSource = await fs.readFile(rendererPath, 'utf8');
        
        console.log('Transpiling...');
        const jsSource = await importer.transpileTypeScript(tsSource);
        
        // Write to temporary file for inspection
        await fs.writeFile('./transpiled-output.js', jsSource);
        console.log('Transpiled code written to transpiled-output.js');
        
        // Show first few lines
        console.log('\nFirst 30 lines of transpiled code:');
        console.log(jsSource.split('\n').slice(0, 30).join('\n'));
        
    } catch (error) {
        console.error('Error:', error.message);
    }
}

testTranspilation();
