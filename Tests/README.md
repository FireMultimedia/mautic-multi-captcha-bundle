# MauticMultiCaptchaBundle Tests

## Overview

This directory contains property-based and unit tests for the ALTCHA integration.

## Running Tests

Since this is a Mautic plugin, tests should be run within a Mautic installation context:

### Option 1: Within Mautic Installation

```bash
# From your Mautic root directory
php bin/phpunit plugins/MauticMultiCaptchaBundle/Tests
```

### Option 2: Standalone (requires dependencies)

```bash
# Install dependencies first
composer install

# Run tests
vendor/bin/phpunit
```

## Test Structure

### Property-Based Tests

Property-based tests verify universal properties across many random inputs (100 iterations minimum).

- **AltchaIntegrationTest::testHmacKeyPersistence**: Verifies that the integration correctly defines the hmac_key field for persistence

### Unit Tests

Unit tests verify specific behaviors:

- **testGetName**: Verifies integration name is "ALTCHA"
- **testGetDisplayName**: Verifies display name is "ALTCHA"  
- **testGetAuthenticationType**: Verifies authentication type is "none"
- **testGetRequiredKeyFields**: Verifies hmac_key field is defined

## Requirements

- PHP 8.1 or higher
- PHPUnit 9.5 or higher
- Mautic 5.x, 6.x, or 7.x

## Notes

Tests are designed to run without external dependencies where possible, using mocks to simulate Mautic's integration system.
