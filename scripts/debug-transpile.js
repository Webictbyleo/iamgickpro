const { AdvancedTemplateImporter } = require('./advanced-template-importer.js');
const fs = require('fs').promises;
const path = require('path');

async function debugTranspile() {
    console.log('🔍 Debug transpilation process...');
    
    const importer = new AdvancedTemplateImporter();
    const rendererPath = path.join(__dirname, '../frontend/src/editor/sdk/DesignRenderer.ts');
    
    try {
        const tsSource = await fs.readFile(rendererPath, 'utf8');
        console.log('✅ TypeScript source loaded');
        
        const jsSource = await importer.transpileTypeScript(tsSource);
        console.log('✅ Transpilation completed');
        
        // Write to file for inspection
        await fs.writeFile('./debug-transpiled.js', jsSource);
        console.log('✅ Transpiled code written to debug-transpiled.js');
        
        // Test syntax
        try {
            new Function(jsSource);
            console.log('✅ Transpiled code has valid syntax');
        } catch (error) {
            console.log('❌ Syntax error:', error.message);
            
            // Show problematic lines
            const lines = jsSource.split('\n');
            lines.forEach((line, idx) => {
                if (line.includes('stage') || line.includes('layer') || line.includes('renderers')) {
                    console.log(`Line ${idx + 1}: ${line}`);
                }
            });
        }
        
    } catch (error) {
        console.error('❌ Error:', error.message);
    }
}

debugTranspile();
