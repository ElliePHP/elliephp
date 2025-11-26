# Cache Component Tests

This directory contains comprehensive tests for the Ellie PHP Cache Component.

## Test Coverage

### Test Suites

1. **SharedTest** - Tests for shared utilities
   - TTL conversion (null, integer, DateInterval)
   - Key validation (PSR-16 compliance)
   - Iterable to array conversion

2. **CacheTest** - Tests for the Cache proxy class
   - Key prefixing functionality
   - All PSR-16 methods
   - Multi-operation prefix handling

3. **FileDriverTest** - Tests for file-based caching
   - Basic CRUD operations
   - TTL and expiration
   - Batch operations
   - Garbage collection

4. **SQLiteDriverTest** - Tests for SQLite-based caching
   - Basic CRUD operations
   - TTL and expiration
   - Batch operations
   - Transaction handling
   - Garbage collection

5. **CacheFactoryTest** - Tests for driver factory
   - Driver creation for all types
   - Configuration validation
   - Directory auto-creation
   - Error handling

## Running Tests

### Run all tests
```bash
composer test
# or
./vendor/bin/phpunit
```

### Run with detailed output
```bash
./vendor/bin/phpunit --testdox
```

### Run specific test suite
```bash
./vendor/bin/phpunit tests/FileDriverTest.php
./vendor/bin/phpunit tests/SQLiteDriverTest.php
./vendor/bin/phpunit tests/CacheTest.php
```

### Run with coverage
```bash
composer test:coverage
```

## Test Statistics

- **Total Tests**: 69
- **Total Assertions**: 127
- **Skipped Tests**: 2 (APCu tests when extension not available)

## What's Tested

### Core Functionality
- ✅ Get/Set/Delete operations
- ✅ Has/Clear operations
- ✅ Batch operations (getMultiple, setMultiple, deleteMultiple)
- ✅ TTL handling (seconds, DateInterval, null/forever)
- ✅ Key validation (PSR-16 compliance)
- ✅ Default values
- ✅ Expiration behavior

### Driver-Specific
- ✅ File: Garbage collection, file system operations
- ✅ SQLite: Transactions, database operations, expired item handling
- ✅ Factory: Driver creation, configuration validation

### Edge Cases
- ✅ Empty keys (should throw exception)
- ✅ Keys with reserved characters (should throw exception)
- ✅ Keys exceeding 255 characters (should throw exception)
- ✅ Expired items
- ✅ Non-existent keys
- ✅ Empty string values
- ✅ Negative TTL values
- ✅ Large TTL values (overflow protection)

## Notes

- APCu tests are skipped when the extension is not available
- Redis tests are not included (requires running Redis server)
- Tests use temporary directories that are cleaned up automatically
- SQLite tests use in-memory databases for speed
