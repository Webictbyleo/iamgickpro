# SVG Processing Test Suite

This document explains how to run the comprehensive test suite for the SVG processing system.

## Prerequisites

1. **PHP 8.4+** with required extensions
2. **Composer dependencies** installed
3. **Proper file permissions** for output directories

```bash
# Install dependencies
composer install

# Ensure proper permissions
chmod +x run_tests.php
mkdir -p tests/output
chmod 755 tests/output
```

## Quick Start

### Run Basic Tests
```bash
# Simple layer type testing
php run_tests.php basic
```

### Run Comprehensive Tests (Recommended)
```bash
# Full test suite with all features
php run_tests.php comprehensive
```

### Run Performance Benchmarks
```bash
# Performance and memory usage tests
php run_tests.php performance
```

### Run All Tests
```bash
# Execute all available test suites
php run_tests.php all
```

## Command Line Options

### Test Suites
- `basic` - Basic layer type rendering tests
- `comprehensive` - Full feature testing including edge cases
- `performance` - Performance benchmarks and memory usage
- `all` - Run all test suites sequentially

### Options
- `--verbose` - Enable detailed output
- `--output=<dir>` - Specify custom output directory
- `--help` - Show usage information

### Examples

```bash
# Verbose comprehensive testing
php run_tests.php comprehensive --verbose

# Performance tests with custom output
php run_tests.php performance --output=/tmp/svg_tests

# All tests with verbose output
php run_tests.php all --verbose --output=./test_results
```

## Test Categories

### 1. Basic Layer Rendering
- ✅ Text layers with various properties
- ✅ Image layers with different sources
- ✅ Shape layers (rectangle, circle, polygon, etc.)
- ✅ Group layers with nesting
- ✅ Media layers (video/audio placeholders)

### 2. Advanced Features
- 🎨 **Gradients & Patterns**: Linear, radial, conic gradients
- 🔄 **Transformations**: Rotation, scaling, skewing
- ✨ **Effects**: Shadows, glows, filters
- 📝 **Typography**: Multi-language, text paths, styling
- 🖼️ **Image Processing**: Filters, cropping, blend modes

### 3. Edge Cases & Error Handling
- ⚠️ Invalid dimensions (zero, negative, very large)
- 🚫 Malformed data and missing properties
- 🔧 Error recovery mechanisms
- 💾 Memory usage with large datasets

### 4. Performance Metrics
- ⚡ Single layer rendering speed
- 📊 Complex document generation
- 💾 Memory usage analysis
- 🔄 Throughput measurements

## Understanding Test Output

### Success Indicators
```
✅ Test Name (12.34ms)    # Test passed with execution time
📊 Performance Metric     # Performance measurement
🎉 ALL TESTS PASSED!     # Complete success
```

### Failure Indicators
```
❌ Test Name: Error message    # Test failed with reason
⚠️ NEEDS ATTENTION!          # Some tests failed
```

### Performance Metrics
```
⏱️ Single Layer Rendering: 1.23ms
📊 Memory Usage: 2.45 MB
🔄 Throughput: 1000 layers/second
```

## Output Files

Tests generate various output files in the specified output directory:

### Generated Files
- `comprehensive_test_report.json` - Detailed test results
- `complex_document.svg` - Sample complex SVG output
- `integration_design_*.svg` - Integration test outputs
- `performance_report.txt` - Performance benchmark results

### Report Structure
```json
{
  "summary": {
    "total_tests": 150,
    "passed_tests": 148,
    "pass_rate": 98.7,
    "duration": 45.2
  },
  "performance_metrics": {
    "Single Layer Rendering": 1.23,
    "Memory Usage": 2456789
  },
  "test_results": [...],
  "categories": {...}
}
```

## Troubleshooting

### Common Issues

#### Permission Errors
```bash
# Fix file permissions
chmod 755 run_tests.php
chmod -R 755 tests/
```

#### Memory Limit Errors
```bash
# Increase PHP memory limit
php -d memory_limit=1G run_tests.php comprehensive
```

#### Missing Dependencies
```bash
# Reinstall Composer dependencies
composer install --no-dev
composer dump-autoload
```

#### Output Directory Issues
```bash
# Create and set permissions
mkdir -p tests/output
chmod 755 tests/output
```

### Performance Issues

If tests run slowly:
1. **Reduce test iterations** in performance tests
2. **Use SSD storage** for better I/O performance
3. **Increase PHP memory limit**
4. **Run tests on dedicated environment**

### Debugging Failed Tests

1. **Enable verbose mode**: `--verbose` flag
2. **Check error logs** in output directory
3. **Review generated SVG files** for visual inspection
4. **Isolate specific test categories**

## Integration with CI/CD

### GitHub Actions Example
```yaml
name: SVG Processing Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
      - name: Install dependencies
        run: composer install
      - name: Run comprehensive tests
        run: php run_tests.php comprehensive --verbose
```

### Docker Testing
```dockerfile
FROM php:8.4-cli
RUN apt-get update && apt-get install -y git unzip
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
RUN composer install
CMD ["php", "run_tests.php", "all", "--verbose"]
```

## Advanced Usage

### Custom Test Environments
```bash
# Test with specific PHP settings
php -d memory_limit=2G -d max_execution_time=600 run_tests.php all

# Test with error reporting
php -d display_errors=1 -d error_reporting=E_ALL run_tests.php comprehensive
```

### Automated Testing Scripts
```bash
#!/bin/bash
# automated_test.sh

echo "Starting SVG Processing Tests..."
php run_tests.php all --verbose --output=./daily_tests

if [ $? -eq 0 ]; then
    echo "✅ All tests passed!"
    # Send success notification
else
    echo "❌ Tests failed!"
    # Send failure notification
    exit 1
fi
```

## Contributing

When adding new tests:

1. **Follow naming conventions**: `test[FeatureName]()`
2. **Add proper documentation**: Describe test purpose
3. **Include error cases**: Test both success and failure scenarios
4. **Update this README**: Document new test categories
5. **Ensure cleanup**: Clean up temporary files and resources

For questions or issues with the test suite, please refer to the project documentation or create an issue in the repository.
