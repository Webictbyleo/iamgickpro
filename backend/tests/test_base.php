<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * Base class for layer renderer tests
 * Provides common functionality and utilities for testing layer renderers
 */
abstract class TestBase
{
    protected function output(string $message): void
    {
        echo $message;
    }

    protected function assertTrue(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            throw new Exception($message ?: 'Assertion failed');
        }
    }

    protected function assertContains(string $needle, string $haystack, string $message = ''): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new Exception($message ?: "String '$needle' not found in haystack");
        }
    }

    protected function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new Exception($message ?: "Expected '$expected', got '$actual'");
        }
    }

    protected function assertEqualsWithDelta(float $expected, float $actual, float $delta, string $message = ''): void
    {
        if (abs($expected - $actual) > $delta) {
            throw new Exception($message ?: "Expected '$expected', got '$actual' (delta: $delta)");
        }
    }

    protected function assertNotNull($value, string $message = ''): void
    {
        if ($value === null) {
            throw new Exception($message ?: 'Value should not be null');
        }
    }

    protected function validateNumber(float $value, float $default, ?float $min = null, ?float $max = null): float
    {
        if ($min !== null && $value < $min) {
            return $default;
        }
        if ($max !== null && $value > $max) {
            return $default;
        }
        return $value;
    }

    protected function validateColor(string $color): string
    {
        // Simple color validation - accept hex colors and basic rgb()
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return $color;
        }
        if (preg_match('/^rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)$/', $color)) {
            return $color;
        }
        return '#000000'; // Default to black
    }
}
