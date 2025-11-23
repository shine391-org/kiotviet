#!/bin/bash
# Pre-commit Checks v2.3 - Balanced & Safe
# 7 Checks: Scope + Tests + Backward Compat + Quality + Migrations + Integration Tests + Coverage

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 Pre-commit Safety Checks v2.3"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

WARNINGS=0
ERRORS=0

# Check bypass flags
SKIP_SCOPE=${SKIP_SCOPE:-false}
SKIP_TESTS=${SKIP_TESTS:-false}

# ============================================
# Check 1: Scope Protection (Simple)
# ============================================
echo ""
echo "[1/5] Checking file scope..."

if [ "$SKIP_SCOPE" = "true" ]; then
    echo "   ⚠️  SCOPE CHECK SKIPPED (SKIP_SCOPE=true)"
    WARNINGS=$((WARNINGS + 1))
else
    # Critical files không được sửa (trừ khi có lý do)
    CRITICAL_FILES=(
        "app/Config/Routes.php"
        "app/Controllers/Auth.php"
        "app/Filters/"
        ".env"
        "app/Config/Database.php"
    )
    
    STAGED=$(git diff --cached --name-only 2>/dev/null || echo "")
    
    if [ -z "$STAGED" ]; then
        echo "   💡 No files staged"
    else
        SCOPE_VIOLATION=0
        while IFS= read -r file; do
            IS_CRITICAL=false
            for critical in "${CRITICAL_FILES[@]}"; do
                if [[ "$file" == *"$critical"* ]]; then
                    IS_CRITICAL=true
                    break
                fi
            done
            
            if [ "$IS_CRITICAL" = true ]; then
                echo "   ⚠️  Critical file: $file"
                echo "      → Are you sure this is needed?"
                echo "      → Use SKIP_SCOPE=true to bypass"
                SCOPE_VIOLATION=1
            else
                echo "   ✅ $file"
            fi
        done <<< "$STAGED"
        
        if [ $SCOPE_VIOLATION -eq 1 ]; then
            ERRORS=$((ERRORS + 1))
        fi
    fi
fi

# ============================================
# Check 2: PHPUnit Tests
# ============================================
echo ""
echo "[2/5] Running PHPUnit tests..."

if [ "$SKIP_TESTS" = "true" ]; then
    echo "   ⚠️  TESTS SKIPPED (SKIP_TESTS=true)"
    WARNINGS=$((WARNINGS + 1))
elif docker ps 2>/dev/null | grep -q meomeo2-api-1; then
    echo "   Running tests..."
    TEST_OUTPUT=$(docker exec meomeo2-api-1 vendor/bin/phpunit --colors=never 2>&1)
    
    if echo "$TEST_OUTPUT" | grep -q "OK"; then
        TESTS=$(echo "$TEST_OUTPUT" | grep -oP 'Tests: \K\d+' || echo "?")
        ASSERTIONS=$(echo "$TEST_OUTPUT" | grep -oP 'Assertions: \K\d+' || echo "?")
        echo "   ✅ $TESTS tests, $ASSERTIONS assertions passed"
    else
        echo "   ❌ Tests failed"
        echo "$TEST_OUTPUT" | grep -A 5 "FAILURES\|ERRORS" | head -10
        ERRORS=$((ERRORS + 1))
    fi
else
    echo "   ⚠️  Docker not running (container: meomeo2-api-1)"
    WARNINGS=$((WARNINGS + 1))
fi

# ============================================
# Check 3: Backward Compatibility
# ============================================
echo ""
echo "[3/5] Checking backward compatibility..."

ROUTES_REMOVED=$(git diff --cached app/Config/Routes.php 2>/dev/null | grep "^-.*routes->" || echo "")

if [ -n "$ROUTES_REMOVED" ]; then
    echo "   ⚠️  Routes may have been removed:"
    echo "$ROUTES_REMOVED" | sed 's/^/      /' | head -5
    echo "   → Verify frontend still works!"
    WARNINGS=$((WARNINGS + 1))
else
    echo "   ✅ No routes removed"
fi

# ============================================
# Check 4: Code Quality
# ============================================
echo ""
echo "[4/5] Checking code quality..."

STAGED_PHP=$(git diff --cached --name-only 2>/dev/null | grep "\.php$" || echo "")

if [ -z "$STAGED_PHP" ]; then
    echo "    No PHP files changed"
else
    QUALITY_ISSUES=0
    while IFS= read -r file; do
        if [ -f "$file" ]; then
            LINES=$(wc -l < "$file")
            
            # Check inline docs for Services/Repos/Controllers
            if [[ "$file" =~ (Services|Repositories|Controllers) ]]; then
                DOCS=$(grep -c "@agent-" "$file" 2>/dev/null || echo 0)
                if [ "$DOCS" -eq 0 ]; then
                    echo "   ⚠️  Missing @agent- docs: $file"
                    QUALITY_ISSUES=1
                fi
            fi
            
            # Check file sizes (flexible limits)
            if [[ "$file" == *"Controller"* ]] && [ $LINES -gt 250 ]; then
                echo "   ⚠️  Large controller: $file ($LINES > 250 lines)"
                QUALITY_ISSUES=1
            elif [[ "$file" == *"Service"* ]] && [ $LINES -gt 500 ]; then
                echo "   ⚠️  Large service: $file ($LINES > 500 lines)"
                QUALITY_ISSUES=1
            elif [[ "$file" == *"Repository"* ]] && [ $LINES -gt 400 ]; then
                echo "   ⚠️  Large repository: $file ($LINES > 400 lines)"
                QUALITY_ISSUES=1
            else
                echo "   ✅ $file ($LINES lines)"
            fi
        fi
    done <<< "$STAGED_PHP"
    
    if [ $QUALITY_ISSUES -eq 1 ]; then
        WARNINGS=$((WARNINGS + 1))
    fi
fi

# ============================================
# Check 5: Database Migrations
# ============================================
echo ""
echo "[5/5] Checking database migrations..."

CHANGED_MODELS=$(git diff --cached --name-only 2>/dev/null | grep "app/Models" || echo "")

if [ -n "$CHANGED_MODELS" ]; then
    NEW_MIGRATIONS=$(git diff --cached --name-only 2>/dev/null | grep "Database/Migrations" || echo "")
    
    if [ -z "$NEW_MIGRATIONS" ]; then
        echo "   💡 Models changed but no migrations added"
        echo "   Changed: $CHANGED_MODELS"
        echo "   → Consider migrations if schema changed"
    else
        echo "   ✅ Migrations found"
    fi
else
    echo "   ✅ No model changes"
fi

# ============================================
# Check 6: Integration Tests (MySQL)
# ============================================
echo ""
echo "[6/6] Running Integration Tests (MySQL)..."
echo "This tests with real MySQL database..."

# Check if db-test is running
if ! docker ps 2>/dev/null | grep -q "db-test"; then
    echo "⚠️  Warning: db-test container not running"
    echo "Run: docker-compose up -d db-test"
    echo "Skipping integration tests..."
elif [ "$SKIP_TESTS" = "true" ]; then
    echo "   ⚠️  INTEGRATION TESTS SKIPPED (SKIP_TESTS=true)"
    WARNINGS=$((WARNINGS + 1))
else
    # Run migrations on test db
    echo "   Running migrations on test database..."
    docker exec meomeo2-api-1 php spark migrate --all --env tests 2>/dev/null
    MIGRATION_STATUS=$?
    
    if [ $MIGRATION_STATUS -ne 0 ]; then
        echo "   ❌ Migration failed for test DB"
        ERRORS=$((ERRORS + 1))
    else
        # Run integration tests
        echo "   Running integration tests..."
        TEST_OUTPUT=$(docker exec meomeo2-api-1 vendor/bin/phpunit -c backend-ci/phpunit.integration.xml --colors=never 2>&1)
        
        if echo "$TEST_OUTPUT" | grep -q "OK"; then
            TESTS=$(echo "$TEST_OUTPUT" | grep -oP 'Tests: \K\d+' || echo "?")
            echo "   ✅ Integration tests PASSED ($TESTS tests)"
        else
            echo "   ❌ Integration tests FAILED"
            echo "$TEST_OUTPUT" | grep -A 5 "FAILURES\|ERRORS" | head -10
            ERRORS=$((ERRORS + 1))
        fi
    fi
fi

# ============================================
# Check 7: Code Coverage (PHPUnit)
# ============================================
echo ""
echo "[7/7] 📊 Code Coverage Check..."

if [ "$SKIP_TESTS" = "true" ]; then
    echo "   ⏭️  Skipped (SKIP_TESTS=true)"
else
    # Check if Docker container is running
    if ! docker ps 2>/dev/null | grep -q meomeo2-api-1; then
        echo "   ⚠️  Docker container not running - skipping coverage check"
        WARNINGS=$((WARNINGS + 1))
    else
        echo "   Running PHPUnit with coverage..."
        
        # Run PHPUnit with coverage-text output
        COVERAGE_OUTPUT=$(docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text --colors=never 2>&1)
        
        # Extract coverage percentage from output
        # PHPUnit outputs: "Lines: XX.XX%"
        COVERAGE_PERCENT=$(echo "$COVERAGE_OUTPUT" | grep -oP 'Lines:\s+\K[0-9.]+(?=%)' | head -1)
        
        if [ -z "$COVERAGE_PERCENT" ]; then
            echo "   ⚠️  Could not determine coverage percentage"
            WARNINGS=$((WARNINGS + 1))
        else
            # Compare coverage with threshold (70%)
            THRESHOLD=70
            
            # Use bc for floating point comparison
            if command -v bc >/dev/null 2>&1; then
                IS_BELOW=$(echo "$COVERAGE_PERCENT < $THRESHOLD" | bc -l)
            else
                # Fallback to integer comparison if bc not available
                COVERAGE_INT=${COVERAGE_PERCENT%.*}
                if [ "$COVERAGE_INT" -lt "$THRESHOLD" ]; then
                    IS_BELOW=1
                else
                    IS_BELOW=0
                fi
            fi
            
            if [ "$IS_BELOW" -eq 1 ]; then
                echo "   ❌ Coverage: ${COVERAGE_PERCENT}% (threshold: ${THRESHOLD}%)"
                echo "   Run: docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-html coverage/"
                ERRORS=$((ERRORS + 1))
            else
                echo "   ✅ Coverage: ${COVERAGE_PERCENT}% (threshold: ${THRESHOLD}%)"
            fi
        fi
    fi
fi

# ============================================
# Summary
# ============================================
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ $ERRORS -gt 0 ]; then
    echo "❌ CRITICAL ERRORS: $ERRORS"
    echo ""
    echo "Cannot commit. Fix errors or use bypass:"
    echo "  SKIP_SCOPE=true bash .ai/pre-commit-checks.sh"
    echo "  SKIP_TESTS=true bash .ai/pre-commit-checks.sh"
    echo ""
    exit 1
fi

if [ $WARNINGS -gt 0 ]; then
    echo "⚠️  Warnings: $WARNINGS (non-blocking)"
fi

echo "✅ Safe to commit! 🚀"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

exit 0
