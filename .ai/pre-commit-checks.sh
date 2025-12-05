#!/bin/bash
# Pre-commit Checks v3.0 - Simplified
# Only essential checks, warnings instead of errors

source "$(dirname "$0")/check-config.sh" 2>/dev/null || true

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 Pre-commit Checks v3.0"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

WARNINGS=0
ERRORS=0

# ============================================
# Check 1: PHP Syntax
# ============================================
echo ""
echo "[1/3] Checking PHP syntax..."

STAGED_PHP=$(git diff --cached --name-only 2>/dev/null | grep "\.php$" || echo "")

if [ -z "$STAGED_PHP" ]; then
    echo "   ✅ No PHP files staged"
else
    SYNTAX_ERRORS=0
    while IFS= read -r file; do
        if [ -f "$file" ]; then
            if ! php -l "$file" 2>&1 | grep -q "No syntax errors"; then
                echo "   ❌ Syntax error: $file"
                SYNTAX_ERRORS=1
            fi
        fi
    done <<< "$STAGED_PHP"
    
    if [ $SYNTAX_ERRORS -eq 0 ]; then
        echo "   ✅ All PHP files valid"
    else
        ERRORS=$((ERRORS + 1))
    fi
fi

# ============================================
# Check 2: Unit Tests (optional)
# ============================================
echo ""
echo "[2/3] Running tests..."

if [ "$SKIP_TESTS" = "true" ]; then
    echo "   ⏭️  Skipped (SKIP_TESTS=true)"
elif ! docker ps 2>/dev/null | grep -q "$DOCKER_CONTAINER"; then
    echo "   ⚠️  Docker not running, skipping tests"
    WARNINGS=$((WARNINGS + 1))
else
    TEST_OUTPUT=$(docker exec $DOCKER_CONTAINER vendor/bin/phpunit --colors=never 2>&1)
    
    if echo "$TEST_OUTPUT" | grep -q "OK\|No tests"; then
        TESTS=$(echo "$TEST_OUTPUT" | grep -oP 'Tests: \K\d+' || echo "0")
        echo "   ✅ Tests passed ($TESTS tests)"
    else
        echo "   ⚠️  Some tests failed (check manually)"
        echo "$TEST_OUTPUT" | grep -E "FAILURES|ERRORS|Failed" | head -3
        WARNINGS=$((WARNINGS + 1))
    fi
fi

# ============================================
# Check 3: File Size (warnings only)
# ============================================
echo ""
echo "[3/3] Checking file sizes..."

if [ -n "$STAGED_PHP" ]; then
    while IFS= read -r file; do
        if [ -f "$file" ]; then
            LINES=$(wc -l < "$file")
            
            if [[ "$file" == *"Controller"* ]] && [ $LINES -gt ${CONTROLLER_MAX_LINES:-350} ]; then
                echo "   ⚠️  Large controller: $file ($LINES lines)"
                WARNINGS=$((WARNINGS + 1))
            elif [[ "$file" == *"Service"* ]] && [ $LINES -gt ${SERVICE_MAX_LINES:-700} ]; then
                echo "   ⚠️  Large service: $file ($LINES lines)"
                WARNINGS=$((WARNINGS + 1))
            elif [[ "$file" == *"Repository"* ]] && [ $LINES -gt ${REPOSITORY_MAX_LINES:-500} ]; then
                echo "   ⚠️  Large repository: $file ($LINES lines)"
                WARNINGS=$((WARNINGS + 1))
            fi
        fi
    done <<< "$STAGED_PHP"
    echo "   ✅ File size check done"
fi

# ============================================
# Summary
# ============================================
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ $ERRORS -gt 0 ]; then
    echo "❌ Errors: $ERRORS (fix before commit)"
    exit 1
fi

if [ $WARNINGS -gt 0 ]; then
    echo "⚠️  Warnings: $WARNINGS (review recommended)"
fi

echo "✅ Ready to commit!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
exit 0
