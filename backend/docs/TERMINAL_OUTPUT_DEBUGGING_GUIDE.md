# Terminal Output Debugging Guide

## Overview
This guide provides best practices for capturing and interpreting terminal output when debugging PHP/Symfony applications, especially when some commands may not show immediate output or appear to hang.

## Common Terminal Output Issues

### 1. Empty or Missing Output
**Problem**: Terminal commands appear to run but show no output
**Solutions**:
- Add explicit output statements with `echo` or `printf`
- Use `2>&1` to capture both stdout and stderr
- Add verbose flags (`-v`, `--verbose`) when available
- Use `set -x` to show command execution in bash scripts

### 2. Buffered Output
**Problem**: Output appears delayed or all at once
**Solutions**:
- Use `php -u` for unbuffered output
- Add `flush()` calls in PHP scripts
- Use `stdbuf -o0` to disable output buffering
- Add `ob_end_flush()` if output buffering is enabled

### 3. Silent Failures
**Problem**: Commands complete without errors but don't show expected results
**Solutions**:
- Check return codes with `echo $?`
- Add error reporting: `php -d error_reporting=E_ALL -d display_errors=1`
- Use `set -e` in bash scripts to exit on errors
- Add explicit success/failure messages

## Terminal Output Capture Techniques

### Basic Output Capture
```bash
# Capture both stdout and stderr
command 2>&1

# Capture to file and display
command 2>&1 | tee output.log

# Check if output is empty
output=$(command 2>&1)
if [ -z "$output" ]; then
    echo "No output received"
else
    echo "$output"
fi
```

### PHP Script Output Enhancement
```php
<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Disable output buffering
if (ob_get_level()) {
    ob_end_flush();
}

// Add explicit output
echo "Script starting...\n";
flush();

try {
    // Your code here
    echo "Operation completed successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Script finished.\n";
?>
```

### Symfony Debugging Commands
```bash
# Check if services are registered
php bin/console debug:container --show-arguments

# Verbose service debugging
php bin/console debug:container service_name -v

# Test service instantiation
php bin/console debug:autowiring ServiceName

# Cache clear with verbose output
php bin/console cache:clear -v

# Check environment variables
php bin/console debug:dotenv
```

## Best Practices for Service Testing

### 1. Service Verification Script Template
```php
#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\DependencyInjection\ContainerBuilder;
use App\Kernel;

echo "=== SERVICE VERIFICATION SCRIPT ===\n";

try {
    $kernel = new Kernel('dev', true);
    $kernel->boot();
    $container = $kernel->getContainer();
    
    echo "✓ Kernel booted successfully\n";
    
    // Test specific service
    $serviceName = 'App\\Service\\YourService';
    
    if ($container->has($serviceName)) {
        echo "✓ Service '$serviceName' is registered\n";
        
        $service = $container->get($serviceName);
        echo "✓ Service instantiated: " . get_class($service) . "\n";
        
        // Test service method if applicable
        if (method_exists($service, 'testMethod')) {
            $result = $service->testMethod();
            echo "✓ Test method executed: " . print_r($result, true) . "\n";
        }
    } else {
        echo "✗ Service '$serviceName' is NOT registered\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "✗ Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "=== VERIFICATION COMPLETE ===\n";
?>
```

### 2. API Testing with Authentication
```php
#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

echo "=== API AUTHENTICATION TEST ===\n";

try {
    // Boot Symfony kernel
    $kernel = new Kernel('dev', true);
    $kernel->boot();
    
    // Create test request with authentication
    $request = Request::create(
        '/api/stock-media/search',
        'GET',
        ['query' => 'test', 'provider' => 'unsplash'],
        [], // cookies
        [], // files
        [
            'HTTP_AUTHORIZATION' => 'Bearer ' . generateTestJWT(),
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ]
    );
    
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

function generateTestJWT(): string {
    // Implementation for generating test JWT token
    // This should match your JWT generation logic
    return 'test_jwt_token_here';
}
?>
```

## Debugging Terminal Output Issues

### 1. Check PHP Configuration
```bash
# Check PHP CLI configuration
php --ini

# Check specific PHP settings
php -i | grep -E "(error_reporting|display_errors|output_buffering)"

# Test basic PHP output
php -r "echo 'PHP is working\n'; flush();"
```

### 2. Environment Debugging
```bash
# Check current working directory
pwd

# Check file permissions
ls -la script.php

# Check if file is executable
chmod +x script.php

# Check PHP syntax
php -l script.php
```

### 3. Step-by-Step Output Verification
```bash
# Run with maximum verbosity
php -v script.php

# Add debug output to command
echo "About to run command..."
php script.php
echo "Command completed with exit code: $?"
```

## Common Symfony Service Issues

### 1. Service Not Found
- Check service registration in `services.yaml`
- Verify class namespace and autoloading
- Run `composer dump-autoload`
- Check if service is marked as public

### 2. Dependency Injection Issues
- Verify constructor parameters match service definitions
- Check if dependencies are properly registered
- Use `debug:container` to inspect service dependencies

### 3. Configuration Issues
- Verify environment variables are loaded
- Check `.env` file syntax
- Use `debug:config` to inspect configuration

## Testing Checklist

Before assuming a command failed:

1. ✅ Check return code: `echo $?`
2. ✅ Run with verbose output: add `-v` flags
3. ✅ Capture stderr: use `2>&1`
4. ✅ Add explicit output statements
5. ✅ Check file permissions and syntax
6. ✅ Verify environment and working directory
7. ✅ Test with minimal reproduction case
8. ✅ Check logs: `tail -f var/log/dev.log`

## Example: Comprehensive Service Test

```bash
#!/bin/bash
set -e  # Exit on any error
set -x  # Show commands being executed

echo "=== COMPREHENSIVE SERVICE TEST ==="

# 1. Environment check
echo "Current directory: $(pwd)"
echo "PHP version: $(php -v | head -n1)"
echo "Symfony version: $(php bin/console --version)"

# 2. Service registration check
echo "Checking service registration..."
php bin/console debug:container App\\Service\\StockMedia\\StockMediaResponseValidator || {
    echo "Service not found in container"
    exit 1
}

# 3. Service instantiation test
echo "Testing service instantiation..."
php -r "
require 'vendor/autoload.php';
use App\Kernel;
\$kernel = new Kernel('dev', true);
\$kernel->boot();
\$container = \$kernel->getContainer();
\$service = \$container->get('App\\\\Service\\\\StockMedia\\\\StockMediaResponseValidator');
echo 'Service instantiated successfully: ' . get_class(\$service) . PHP_EOL;
"

# 4. Integration test
echo "Running integration test..."
php test_integration.php

echo "=== ALL TESTS PASSED ==="
```

This comprehensive approach ensures that we capture all output and properly diagnose any issues that arise during testing and debugging.
