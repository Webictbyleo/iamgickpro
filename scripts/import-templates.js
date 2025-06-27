#!/usr/bin/env node

/**
 * Template Import CLI
 * 
 * Simple command-line interface for importing design templates
 * from the GitHub repository into the IAMGickPro platform.
 * 
 * Usage: ./import-templates.js <command> [options]
 */

const { spawn } = require('child_process');
const path = require('path');

const COMMANDS = {
    'test': {
        description: 'Test import with 1 template (dry run)',
        script: 'advanced-template-importer.js',
        args: ['--limit', '1', '--dry-run']
    },
    'sample': {
        description: 'Import 5 templates (dry run preview)',
        script: 'advanced-template-importer.js',
        args: ['--limit', '5', '--dry-run']
    },
    'social': {
        description: 'Import social media templates (10 max)',
        script: 'advanced-template-importer.js',
        args: ['--category', 'social-media', '--limit', '10']
    },
    'presentation': {
        description: 'Import presentation templates (10 max)',
        script: 'advanced-template-importer.js',
        args: ['--category', 'presentation', '--limit', '10']
    },
    'print': {
        description: 'Import print templates (10 max)',
        script: 'advanced-template-importer.js',
        args: ['--category', 'print', '--limit', '10']
    },
    'small': {
        description: 'Import 20 templates (mixed categories)',
        script: 'advanced-template-importer.js',
        args: ['--limit', '20']
    },
    'medium': {
        description: 'Import 50 templates (mixed categories)',
        script: 'advanced-template-importer.js',
        args: ['--limit', '50']
    },
    'all': {
        description: 'Import all templates (use with caution)',
        script: 'advanced-template-importer.js',
        args: []
    },
    'force-all': {
        description: 'Force re-import all templates',
        script: 'advanced-template-importer.js',
        args: ['--force']
    }
};

function showHelp() {
    console.log(`
🎨 Template Import CLI

Usage: ./import-templates.js <command> [additional-options]

Available commands:
`);

    Object.entries(COMMANDS).forEach(([cmd, config]) => {
        console.log(`  ${cmd.padEnd(12)} - ${config.description}`);
    });

    console.log(`
Additional options (append to any command):
  --force           Force re-import existing templates
  --dry-run         Show what would be imported without doing it
  --limit <n>       Limit number of templates to process
  --no-previews     Skip generating preview images

Examples:
  ./import-templates.js test
  ./import-templates.js social --force
  ./import-templates.js sample --limit 3
  ./import-templates.js all --dry-run

Note: Make sure the backend server is running on http://localhost:8000
    `);
}

function runCommand(command, additionalArgs = []) {
    const config = COMMANDS[command];
    if (!config) {
        console.error(`❌ Unknown command: ${command}`);
        console.error(`Available commands: ${Object.keys(COMMANDS).join(', ')}`);
        process.exit(1);
    }

    console.log(`🚀 Running: ${config.description}`);
    console.log(`Command: node ${config.script} ${[...config.args, ...additionalArgs].join(' ')}`);
    console.log('');

    const scriptPath = path.join(__dirname, config.script);
    const allArgs = [...config.args, ...additionalArgs];

    const child = spawn('node', [scriptPath, ...allArgs], {
        stdio: 'inherit',
        cwd: __dirname
    });

    child.on('error', (error) => {
        console.error(`❌ Failed to start process: ${error.message}`);
        process.exit(1);
    });

    child.on('close', (code) => {
        if (code === 0) {
            console.log(`\n✅ Command completed successfully!`);
        } else {
            console.log(`\n❌ Command failed with exit code ${code}`);
            process.exit(code);
        }
    });
}

// Parse command line arguments
const args = process.argv.slice(2);

if (args.length === 0 || args[0] === '--help' || args[0] === '-h') {
    showHelp();
    process.exit(0);
}

const command = args[0];
const additionalArgs = args.slice(1);

// Check for dependencies
try {
    require.resolve('./advanced-template-importer.js');
} catch (error) {
    console.error(`❌ Script not found: advanced-template-importer.js`);
    console.error(`Make sure you're running this from the scripts directory.`);
    process.exit(1);
}

runCommand(command, additionalArgs);
