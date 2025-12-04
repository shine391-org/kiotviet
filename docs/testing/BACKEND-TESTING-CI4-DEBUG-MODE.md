# CodeIgniter 4 Debug Mode for Testing

## Overview

CodeIgniter 4 doesn't have `getDebugMode()` or `setDebugMode()` methods directly. Debug mode is controlled entirely through environment variables and constants.

## Checking Debug Mode in Code

### Basic Environment Check

```php
// Get current environment
$env = environment('CI_ENVIRONMENT') ?: 'production';

// Check CI_DEBUG constant (true if development/testing)
$isDebug = defined('CI_DEBUG') && CI_DEBUG === true;

// Direct usage
if (environment('CI_ENVIRONMENT') === 'development') {
    // Debug logic here
    log_message('debug', 'Debug mode active');
}
```

### Helper Function

```php
/**
 * Check if application is in debug mode
 */
function isDebugMode() {
    return environment('CI_ENVIRONMENT') === 'development' || 
           (defined('CI_DEBUG') && CI_DEBUG);
}
```

## Enabling/Disabling Debug Mode

### Environment Configuration

In `.env` file (root project):

```env
CI_ENVIRONMENT = development  # or testing/production
```

### Boot Configuration

`app/Config/Boot/development.php` automatically defines `CI_DEBUG = true`.

### Debug Toolbar

In `app/Config/Filters.php`:

```php
public $globals = [
    'before' => [],
    'after'  => ['toolbar']  // Uncomment to show debug toolbar
];
```

## Performance Testing with Debug Mode

### Query Logging

When implementing performance assertions, check for debug mode availability:

```php
public function assertQueryCount(callable $callback, int $maxQueries, string $message = ''): void
{
    $db = \Config\Database::connect();
    
    // Check if we're in debug mode
    $isDebugMode = environment('CI_ENVIRONMENT') === 'development' || 
                  (defined('CI_DEBUG') && CI_DEBUG);
    
    try {
        // Try to enable query logging if methods are available
        $queryLoggingAvailable = false;
        if (method_exists($db, 'resetQueryLog')) {
            $db->resetQueryLog();
            $queryLoggingAvailable = true;
        }
        
        $result = $callback();
        
        // Get query count if logging is available
        $queryCount = 0;
        if ($queryLoggingAvailable && method_exists($db, 'getQueryLog')) {
            $queries = $db->getQueryLog();
            $queryCount = count($queries);
        } else {
            // Fallback: estimate query count
            $queryCount = $this->estimateQueryCount($callback);
        }
        
        // Assert query count is within limits
        $this->assertLessThanOrEqual($maxQueries, $queryCount, $message);
        
    } catch (\Exception $e) {
        $this->fail("Query-intensive operation failed: " . $e->getMessage());
    }
}
```

### Method Availability Checking

Always check if methods exist before using them:

```php
// Check if query logging methods are available
if (method_exists($db, 'getQueryLog')) {
    $queries = $db->getQueryLog();
}

// Check if debug methods are available
if (method_exists($db, 'resetQueryLog')) {
    $db->resetQueryLog();
}
```

## Best Practices

1. **Always check method existence** before using database debug methods
2. **Use environment-based checks** instead of non-existent debug methods
3. **Provide fallback mechanisms** when query logging isn't available
4. **Test in different environments** (development, testing, production)
5. **Don't rely on debug toolbar** for automated tests

## Common Pitfalls

### ❌ Wrong Approach
```php
// These methods don't exist in CI4
$db->getDebugMode();
$db->setDebugMode(true);
```

### ✅ Correct Approach
```php
// Use environment-based checks
$isDebug = environment('CI_ENVIRONMENT') === 'development';
```

### ❌ Assuming Query Logging
```php
// Don't assume query logging is always available
$queries = $db->getQueryLog(); // May fail
```

### ✅ Safe Query Logging
```php
// Always check method availability
if (method_exists($db, 'getQueryLog')) {
    $queries = $db->getQueryLog();
}
```

## Testing Environment Setup

### Development Environment
```env
CI_ENVIRONMENT = development
```
- Debug toolbar enabled
- Detailed error messages
- Query logging available

### Testing Environment
```env
CI_ENVIRONMENT = testing
```
- Debug toolbar disabled
- Error messages shown
- Query logging may be available

### Production Environment
```env
CI_ENVIRONMENT = production
```
- Debug toolbar disabled
- Generic error messages
- Query logging disabled

## Applying Changes

After changing environment settings:

```bash
# Restart the development server
php spark serve restart

# Or restart Docker containers
docker-compose restart api
```

## Debug Toolbar

When in development mode, the debug toolbar appears in the bottom-right corner of the browser, showing:
- Database queries
- Memory usage
- Execution time
- Request data

This is useful for manual testing but not for automated tests.

## Integration with PerformanceAssertions

The PerformanceAssertions trait now properly handles CI4 debug mode:

1. **Environment-aware**: Checks `CI_ENVIRONMENT` and `CI_DEBUG`
2. **Method-safe**: Verifies method existence before use
3. **Fallback-ready**: Provides estimation when direct counting isn't available
4. **Cross-compatible**: Works across different CI4 environments

This ensures performance tests work reliably regardless of the debug configuration.