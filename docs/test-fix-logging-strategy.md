# Test Fix Logging Strategy

## Overview
Chiến lược logging chi tiết để theo dõi quá trình fix tests sau khi thay đổi database schema. Mọi bước đều được ghi lại để dễ dàng kiểm tra và debug.

## Log Directory Structure

```
logs/test-fixes/
├── 2025-12-02/
│   ├── 01-initial-test-run/
│   │   ├── full-test-output.log
│   │   ├── constraint-violations.log
│   │   ├── test-failures-summary.json
│   │   └── error-analysis.md
│   ├── 02-cash-transaction-fixes/
│   │   ├── before-fix.log
│   │   ├── after-fix.log
│   │   ├── changed-files.txt
│   │   └── validation.log
│   ├── 03-order-total-fixes/
│   │   ├── before-fix.log
│   │   ├── after-fix.log
│   │   ├── changed-files.txt
│   │   └── validation.log
│   ├── 04-order-number-fixes/
│   ├── 05-category-link-fixes/
│   ├── 06-foreign-key-fixes/
│   ├── 07-soft-delete-fixes/
│   └── 08-final-validation/
│       ├── final-test-run.log
│       ├── coverage-report.log
│       └── success-summary.md
└── archive/
    └── previous-runs/
```

## Logging Commands & Scripts

### 1. Initial Test Run with Full Logging

```bash
#!/bin/bash
# scripts/run-initial-test-logging.sh

DATE=$(date +%Y-%m-%d)
TIME=$(date +%H-%M-%S)
LOG_DIR="logs/test-fixes/$DATE/01-initial-test-run"

# Create log directory
mkdir -p "$LOG_DIR"

echo "=== Starting Initial Test Run ===" | tee "$LOG_DIR/test-run-start.log"
echo "Timestamp: $(date)" | tee -a "$LOG_DIR/test-run-start.log"

# Run full test suite with detailed output
echo "Running full test suite..." | tee -a "$LOG_DIR/test-run-start.log"
docker exec meomeo2-api-1 vendor/bin/phpunit --verbose --log-junit "$LOG_DIR/junit.xml" --coverage-text "$LOG_DIR/coverage.txt" > "$LOG_DIR/full-test-output.log" 2>&1

# Extract constraint violations
echo "Extracting constraint violations..." | tee -a "$LOG_DIR/test-run-start.log"
grep -i "constraint\|violation\|check constraint\|foreign key" "$LOG_DIR/full-test-output.log" > "$LOG_DIR/constraint-violations.log" 2>&1

# Extract test failures
echo "Extracting test failures..." | tee -a "$LOG_DIR/test-run-start.log"
grep -A 5 -B 5 "FAILURES\|ERRORS\|FAILED" "$LOG_DIR/full-test-output.log" > "$LOG_DIR/test-failures.log" 2>&1

# Create summary JSON
echo "Creating failure summary..." | tee -a "$LOG_DIR/test-run-start.log"
php scripts/analyze-test-failures.php "$LOG_DIR/full-test-output.log" > "$LOG_DIR/test-failures-summary.json"

echo "=== Initial Test Run Complete ===" | tee -a "$LOG_DIR/test-run-start.log"
echo "Logs saved to: $LOG_DIR" | tee -a "$LOG_DIR/test-run-start.log"
```

### 2. Category-Specific Fix Logging

```bash
#!/bin/bash
# scripts/run-category-fix-logging.sh

CATEGORY=$1
DESCRIPTION=$2

if [ -z "$CATEGORY" ] || [ -z "$DESCRIPTION" ]; then
    echo "Usage: $0 <category> <description>"
    echo "Example: $0 02-cash-transaction-fixes 'Fix cash transaction amount constraints'"
    exit 1
fi

DATE=$(date +%Y-%m-%d)
TIME=$(date +%H-%M-%S)
LOG_DIR="logs/test-fixes/$DATE/$CATEGORY"

# Create log directory
mkdir -p "$LOG_DIR"

echo "=== Starting $DESCRIPTION ===" | tee "$LOG_DIR/fix-start.log"
echo "Timestamp: $(date)" | tee -a "$LOG_DIR/fix-start.log"

# Run tests before fix
echo "Running tests BEFORE fix..." | tee -a "$LOG_DIR/fix-start.log"
docker exec meomeo2-api-1 vendor/bin/phpunit --filter "$CATEGORY" > "$LOG_DIR/before-fix.log" 2>&1

BEFORE_ERRORS=$(grep -c "FAILURES\|ERRORS" "$LOG_DIR/before-fix.log" || echo "0")
echo "Errors before fix: $BEFORE_ERRORS" | tee -a "$LOG_DIR/fix-start.log"

# Note: Actual file changes would be done here
echo "Ready to apply fixes for $CATEGORY" | tee -a "$LOG_DIR/fix-start.log"
echo "After applying fixes, run: ./scripts/validate-category-fix.sh $CATEGORY" | tee -a "$LOG_DIR/fix-start.log"
```

### 3. Validation After Fixes

```bash
#!/bin/bash
# scripts/validate-category-fix.sh

CATEGORY=$1

if [ -z "$CATEGORY" ]; then
    echo "Usage: $0 <category>"
    exit 1
fi

DATE=$(date +%Y-%m-%d)
LOG_DIR="logs/test-fixes/$DATE/$CATEGORY"

echo "=== Validating $CATEGORY Fixes ===" | tee "$LOG_DIR/validation.log"
echo "Timestamp: $(date)" | tee -a "$LOG_DIR/validation.log"

# Run tests after fix
echo "Running tests AFTER fix..." | tee -a "$LOG_DIR/validation.log"
docker exec meomeo2-api-1 vendor/bin/phpunit --filter "$CATEGORY" > "$LOG_DIR/after-fix.log" 2>&1

AFTER_ERRORS=$(grep -c "FAILURES\|ERRORS" "$LOG_DIR/after-fix.log" || echo "0")
echo "Errors after fix: $AFTER_ERRORS" | tee -a "$LOG_DIR/validation.log"

# Compare results
BEFORE_ERRORS=$(grep -c "FAILURES\|ERRORS" "$LOG_DIR/before-fix.log" || echo "0")
IMPROVEMENT=$((BEFORE_ERRORS - AFTER_ERRORS))

echo "=== Validation Results ===" | tee -a "$LOG_DIR/validation.log"
echo "Before fix: $BEFORE_ERRORS errors" | tee -a "$LOG_DIR/validation.log"
echo "After fix: $AFTER_ERRORS errors" | tee -a "$LOG_DIR/validation.log"
echo "Improvement: $IMPROVEMENT errors fixed" | tee -a "$LOG_DIR/validation.log"

if [ $AFTER_ERRORS -eq 0 ]; then
    echo "✅ All tests in $CATEGORY are now passing!" | tee -a "$LOG_DIR/validation.log"
else
    echo "⚠️  Still have $AFTER_ERRORS errors in $CATEGORY" | tee -a "$LOG_DIR/validation.log"
fi

# Log changed files
echo "Changed files during this fix:" | tee -a "$LOG_DIR/validation.log"
git diff --name-only HEAD~1 >> "$LOG_DIR/changed-files.txt" 2>&1
cat "$LOG_DIR/changed-files.txt" | tee -a "$LOG_DIR/validation.log"
```

## PHP Analysis Script

### Create: `scripts/analyze-test-failures.php`

```php
<?php
/**
 * Analyze test failures and create structured JSON summary
 */

if ($argc < 2) {
    echo "Usage: php analyze-test-failures.php <test-output-log-file>\n";
    exit(1);
}

$logFile = $argv[1];
if (!file_exists($logFile)) {
    echo "Log file not found: $logFile\n";
    exit(1);
}

$content = file_get_contents($logFile);
$lines = explode("\n", $content);

$summary = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_tests' => 0,
    'failures' => 0,
    'errors' => 0,
    'constraint_violations' => [],
    'foreign_key_errors' => [],
    'check_constraint_errors' => [],
    'null_constraint_errors' => [],
    'unique_constraint_errors' => [],
    'soft_delete_issues' => [],
    'failed_tests' => []
];

// Parse test results
foreach ($lines as $line) {
    // Count total tests
    if (preg_match('/Tests:\s*(\d+),/', $line, $matches)) {
        $summary['total_tests'] = (int)$matches[1];
    }
    
    // Count failures and errors
    if (preg_match('/Failures:\s*(\d+),/', $line, $matches)) {
        $summary['failures'] = (int)$matches[1];
    }
    if (preg_match('/Errors:\s*(\d+),/', $line, $matches)) {
        $summary['errors'] = (int)$matches[1];
    }
    
    // Extract constraint violations
    if (stripos($line, 'constraint') !== false) {
        $summary['constraint_violations'][] = trim($line);
        
        // Categorize specific constraint types
        if (stripos($line, 'foreign key') !== false) {
            $summary['foreign_key_errors'][] = trim($line);
        } elseif (stripos($line, 'check constraint') !== false) {
            $summary['check_constraint_errors'][] = trim($line);
        } elseif (stripos($line, 'cannot be null') !== false) {
            $summary['null_constraint_errors'][] = trim($line);
        } elseif (stripos($line, 'duplicate entry') !== false || stripos($line, 'unique') !== false) {
            $summary['unique_constraint_errors'][] = trim($line);
        }
    }
    
    // Extract failed test names
    if (preg_match('/^\d+\) ([^(]+)\(/', $line, $matches)) {
        $summary['failed_tests'][] = trim($matches[1]);
    }
    
    // Extract soft delete related issues
    if (stripos($line, 'deleted_at') !== false || stripos($line, 'soft delete') !== false) {
        $summary['soft_delete_issues'][] = trim($line);
    }
}

// Output JSON summary
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

// Also create a readable markdown summary
$mdFile = str_replace('.log', '-analysis.md', $logFile);
$md = "# Test Failure Analysis\n\n";
$md .= "**Generated:** " . $summary['timestamp'] . "\n\n";
$md .= "## Summary\n\n";
$md .= "- **Total Tests:** " . $summary['total_tests'] . "\n";
$md .= "- **Failures:** " . $summary['failures'] . "\n";
$md .= "- **Errors:** " . $summary['errors'] . "\n\n";

$md .= "## Constraint Violations\n\n";
if (!empty($summary['constraint_violations'])) {
    foreach ($summary['constraint_violations'] as $violation) {
        $md .= "- " . htmlspecialchars($violation) . "\n";
    }
} else {
    $md .= "No constraint violations found.\n";
}

$md .= "\n## Failed Tests\n\n";
if (!empty($summary['failed_tests'])) {
    foreach ($summary['failed_tests'] as $test) {
        $md .= "- " . htmlspecialchars($test) . "\n";
    }
} else {
    $md .= "No failed tests found.\n";
}

file_put_contents($mdFile, $md);
echo "Analysis saved to: $mdFile\n";
```

## Daily Log Template

### Create: `logs/test-fixes/log-template.md`

```markdown
# Test Fix Log - {DATE}

## Progress Overview
- **Phase:** {PHASE_DESCRIPTION}
- **Started:** {START_TIME}
- **Completed:** {END_TIME}
- **Status:** {STATUS}

## Commands Executed
```bash
{COMMANDS}
```

## Results Summary
- **Tests Run:** {TOTAL_TESTS}
- **Passing:** {PASSING_TESTS}
- **Failing:** {FAILING_TESTS}
- **Errors:** {ERROR_COUNT}

## Issues Fixed
### {CATEGORY_1}
- **Files Modified:** {FILE_LIST}
- **Issues Resolved:** {ISSUE_COUNT}
- **Validation:** ✅ PASSED / ❌ FAILED

### {CATEGORY_2}
- **Files Modified:** {FILE_LIST}
- **Issues Resolved:** {ISSUE_COUNT}
- **Validation:** ✅ PASSED / ❌ FAILED

## Remaining Issues
{REMAINING_ISSUES}

## Next Steps
{NEXT_STEPS}

## Notes
{NOTES}
```

## Automated Logging Workflow

### 1. Setup Script
```bash
#!/bin/bash
# scripts/setup-test-logging.sh

DATE=$(date +%Y-%m-%d)
BASE_DIR="logs/test-fixes/$DATE"

# Create directory structure
mkdir -p "$BASE_DIR"/{01-initial-test-run,02-cash-transaction-fixes,03-order-total-fixes,04-order-number-fixes,05-category-link-fixes,06-foreign-key-fixes,07-soft-delete-fixes,08-final-validation}

# Copy templates
cp docs/test-fixes/log-template.md "$BASE_DIR/daily-log.md"

echo "Logging structure created for $DATE"
echo "Base directory: $BASE_DIR"
```

### 2. Progress Tracking Script
```bash
#!/bin/bash
# scripts/track-progress.sh

DATE=$(date +%Y-%m-%d)
LOG_DIR="logs/test-fixes/$DATE"

echo "=== Test Fix Progress Report ===" | tee "$LOG_DIR/progress-report.log"
echo "Date: $DATE" | tee -a "$LOG_DIR/progress-report.log"
echo "Time: $(date)" | tee -a "$LOG_DIR/progress-report.log"

# Check each phase
for phase in {01..08}; do
    phase_dir="$LOG_DIR/$phase"*
    if [ -d "$phase_dir" ]; then
        status="⏳ Pending"
        if [ -f "$phase_dir/validation.log" ]; then
            if grep -q "✅ All tests" "$phase_dir/validation.log"; then
                status="✅ Complete"
            elif grep -q "⚠️ Still have" "$phase_dir/validation.log"; then
                status="⚠️ Partial"
            else
                status="🔄 In Progress"
            fi
        fi
        echo "$phase: $status" | tee -a "$LOG_DIR/progress-report.log"
    fi
done

echo "=== End Progress Report ===" | tee -a "$LOG_DIR/progress-report.log"
```

## Usage Instructions

### 1. Start New Test Fix Session
```bash
# Setup logging for today
./scripts/setup-test-logging.sh

# Run initial test analysis
./scripts/run-initial-test-logging.sh

# Check progress
./scripts/track-progress.sh
```

### 2. Work on Specific Category
```bash
# Start working on cash transaction fixes
./scripts/run-category-fix-logging.sh "02-cash-transaction-fixes" "Fix cash transaction amount constraints"

# After making changes, validate
./scripts/validate-category-fix.sh "02-cash-transaction-fixes"
```

### 3. Final Validation
```bash
# Run final validation
./scripts/run-final-validation.sh

# Generate complete report
./scripts/generate-final-report.sh
```

## Log Analysis Commands

### Quick Check of Current Status
```bash
# See today's progress
cat logs/test-fixes/$(date +%Y-%m-%d)/progress-report.log

# See constraint violations
cat logs/test-fixes/$(date +%Y-%m-%d)/01-initial-test-run/constraint-violations.log

# See failure summary
cat logs/test-fixes/$(date +%Y-%m-%d)/01-initial-test-run/test-failures-summary.json | jq .
```

### Compare Before/After
```bash
# Compare error counts
echo "Before:" && grep -c "FAILURES\|ERRORS" logs/test-fixes/$(date +%Y-%m-%d)/02-cash-transaction-fixes/before-fix.log
echo "After:" && grep -c "FAILURES\|ERRORS" logs/test-fixes/$(date +%Y-%m-%d)/02-cash-transaction-fixes/after-fix.log
```

Chiến lược logging này đảm bảo mọi bước được ghi lại chi tiết, giúp dễ dàng theo dõi tiến độ và debug khi cần thiết.