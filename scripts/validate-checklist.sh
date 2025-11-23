#!/bin/bash
# Checklist Validation Script
# Validates that FE-TEST-CHECKLIST.md is complete and signed off

set -e

CHECKLIST_FILE="docs/testing/FE-TEST-CHECKLIST.md"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 Validating Frontend Test Checklist"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Check if file exists
if [ ! -f "$CHECKLIST_FILE" ]; then
    echo "❌ Error: Checklist file not found: $CHECKLIST_FILE"
    exit 1
fi

# Count unchecked boxes
UNCHECKED=$(grep -c '^\s*- \[ \]' "$CHECKLIST_FILE" || true)

if [ "$UNCHECKED" -gt 0 ]; then
    echo "❌ FAILED: Found $UNCHECKED unchecked item(s)"
    echo ""
    echo "Unchecked items:"
    grep -n '^\s*- \[ \]' "$CHECKLIST_FILE" || true
    echo ""
    echo "Please complete all checklist items before committing."
    echo ""
    exit 1
fi

# Check for sign-off
if ! grep -q 'Signed off by:' "$CHECKLIST_FILE"; then
    echo "❌ FAILED: Missing sign-off"
    echo ""
    echo "Please add a sign-off line at the end of the checklist:"
    echo "  Signed off by: @your-github-username"
    echo ""
    exit 1
fi

echo "✅ All checklist items completed"
echo "✅ Sign-off found"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Checklist validation PASSED"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
exit 0
