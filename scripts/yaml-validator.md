---
title: "YAML Validation Script"
id: "YAML-VALIDATOR-01"
type: "Script Documentation"
purpose: "Documentation for automated YAML validation system to ensure documentation quality and consistency."
location: "scripts"
tags: ["yaml", "validation", "automation", "ci-cd"]
related_to:
  - id: "DOCS-INDEX-GENERATOR-01"
    description: "Documentation index generator that uses this validator"
  - id: "DOC-AUDIT-2025-11-26"
    description: "Audit report that identified need for YAML validation"
---

# YAML Validation Script

## Overview

This automated validation system ensures all documentation files maintain consistent YAML structure and follow established standards. It integrates with CI/CD pipelines to prevent documentation quality issues.

## Script Location

`scripts/yaml-validator.php`

## Validation Rules

### 1. Required Fields Validation

#### All Document Types Must Have:
```yaml
---
id: "required"           # Unique identifier across all docs
title: "required"        # Human-readable title
type: "required"         # Document type (Task, Session, Guide, Audit)
status: "required"       # Current status
location: "required"     # File location path
---
```

#### Type-Specific Requirements:

**Tasks (type: Task/Refactoring/New Module):**
```yaml
---
priority: "required"      # CRITICAL/HIGH/MEDIUM/LOW
effort: "required"        # Time estimate (X days/Y weeks)
assigned_to: "required"   # Who is working on it
dependencies: []          # Array of dependent task IDs
---
```

**Session Logs (type: Session Log):**
```yaml
---
session_date: "required"   # YYYY-MM-DD format
participants: []          # Array of participants
duration: "required"       # Time spent (X hours/Y days)
related_tasks: []         # Array of related task IDs
---
```

**Guides (type: Guide):**
```yaml
---
version: "required"       # Semantic version (X.Y.Z)
last_updated: "required"  # YYYY-MM-DD format
tags: []                # Array of searchable tags
related_to: []          # Array of related document IDs
---
```

### 2. Field Format Validation

#### ID Format:
- Must be unique across all documentation
- Format: `CATEGORY-TYPE-NUMBER` (e.g., `REFACTOR-001`)
- No spaces or special characters except hyphens

#### Status Values:
- **Tasks**: `Draft`, `In Progress`, `Testing`, `Review`, `Completed`, `Blocked`
- **Guides**: `Draft`, `Active`, `Deprecated`, `Archived`
- **Audits**: `In Progress`, `Completed`, `Rejected`
- **Session Logs**: `Planned`, `In Progress`, `Completed`

#### Date Formats:
- `YYYY-MM-DD` for dates
- `YYYY-MM-DDTHH:MM:SSZ` for timestamps
- Relative dates: `2-3 days`, `1 week`

### 3. Cross-Reference Validation

#### Related To Links:
```yaml
related_to:
  - id: "EXISTING-ID"     # Must reference existing document
    description: "required"   # Must describe relationship
```

#### Bidirectional Validation:
- If Document A references Document B
- Document B should reference Document A (where appropriate)
- Warn on missing back-references

#### Circular Reference Detection:
- Detect A → B → C → A loops
- Flag as error for documentation cycles

### 4. Content Quality Validation

#### Title Standards:
- Must be descriptive and unique
- Should include document ID for clarity
- Maximum 100 characters

#### Description Standards:
- Must explain document purpose clearly
- Minimum 50 characters for guides
- Maximum 500 characters for summaries

#### Tag Standards:
- Must be lowercase
- Use hyphens for multi-word tags
- Maximum 10 tags per document

## Usage

### Command Line Interface
```bash
# Validate all documentation
php scripts/yaml-validator.php

# Validate specific directory
php scripts/yaml-validator.php --path=docs/tasks

# Validate specific file
php scripts/yaml-validator.php --file=docs/tasks/DONE/refactor/REFACTOR-001-ProductService.md

# Generate report only (no exit on error)
php scripts/yaml-validator.php --report-only

# Output JSON for CI/CD integration
php scripts/yaml-validator.php --format=json
```

### CI/CD Integration

#### GitHub Actions Example
```yaml
# .github/workflows/yaml-validation.yml
name: YAML Validation
on:
  push:
    paths: ['docs/**/*.md']
  pull_request:
    paths: ['docs/**/*.md']

jobs:
  validate-yaml:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - name: Validate YAML
        run: |
          php scripts/yaml-validator.php --format=json > validation-results.json
          
      - name: Upload Results
        uses: actions/upload-artifact@v3
        with:
          name: validation-results
          path: validation-results.json
          
      - name: Check Results
        run: |
          if [ -f validation-results.json ]; then
            errors=$(jq '.errors | length' validation-results.json)
            if [ "$errors" -gt 0 ]; then
              echo "❌ YAML validation failed with $errors errors"
              jq '.errors' validation-results.json
              exit 1
            else
              echo "✅ All YAML validation passed"
            fi
          fi
```

#### Pre-commit Hook
```bash
#!/bin/sh
# .git/hooks/pre-commit

echo "🔍 Validating YAML documentation..."

# Run validator
php scripts/yaml-validator.php --format=json > .validation-results.json

# Check results
errors=$(jq '.errors | length' .validation-results.json)
if [ "$errors" -gt 0 ]; then
  echo ""
  echo "❌ YAML validation failed:"
  jq -r '.errors[] | "  - \(.file): \(.message)"' .validation-results.json
  echo ""
  echo "Please fix the above errors before committing."
  rm .validation-results.json
  exit 1
fi

echo "✅ All YAML validation passed"
rm .validation-results.json
```

## Validation Output

### Success Output
```
✅ YAML Validation Complete
📊 Summary:
  - Total files: 45
  - Valid files: 45
  - Errors: 0
  - Warnings: 3

⚠️ Warnings:
  - docs/tasks/DONE/Task-001-Inventory.md: Missing 'completed_date' field
  - docs/session-logs/2025-11-24-TASK-01-PAYMENT-METHODS.md: No back-reference from TASK_01_PAYMENT_METHODS
  - docs/testing/FE-TESTING-GUIDE.md: Tags should be lowercase
```

### Error Output
```
❌ YAML Validation Failed
🚨 Critical Errors:
  - docs/tasks/new/CUS-002.MD: Missing required field 'id'
  - docs/session-logs/2025-11-25-MYSQL.md: Invalid status 'In Progress' (should be 'Completed')
  - docs/plans/BACKEND-REFACTOR-PLAN.md: Duplicate ID 'BACKEND-REFACTOR-PLAN-01'

📊 Summary:
  - Total files: 45
  - Valid files: 42
  - Errors: 3
  - Warnings: 5

💡 Fix the above errors and re-run validation.
```

### JSON Output (for CI/CD)
```json
{
  "summary": {
    "total_files": 45,
    "valid_files": 42,
    "errors": 3,
    "warnings": 5,
    "status": "failed"
  },
  "errors": [
    {
      "file": "docs/tasks/new/CUS-002.MD",
      "line": 1,
      "field": "id",
      "message": "Missing required field 'id'",
      "severity": "error"
    }
  ],
  "warnings": [
    {
      "file": "docs/tasks/DONE/Task-001-Inventory.md",
      "field": "completed_date",
      "message": "Missing 'completed_date' field for completed task",
      "severity": "warning"
    }
  ],
  "statistics": {
    "by_type": {
      "Task": 15,
      "Session Log": 12,
      "Guide": 8,
      "Audit": 3,
      "Plan": 2
    },
    "by_status": {
      "Completed": 18,
      "In Progress": 8,
      "Active": 6,
      "Draft": 3
    }
  }
}
```

## Advanced Features

### 1. Auto-Fix Capabilities
```bash
# Auto-fix common issues
php scripts/yaml-validator.php --auto-fix

# What it fixes:
# - Adds missing required fields with default values
# - Standardizes date formats
# - Sorts YAML fields alphabetically
# - Removes duplicate entries in arrays
```

### 2. Schema Validation
```bash
# Validate against custom schema
php scripts/yaml-validator.php --schema=schemas/documentation-schema.json

# Example schema validation:
# - Enforces specific field types
# - Validates enum values
# - Checks field dependencies
```

### 3. Performance Monitoring
```bash
# Check validation performance
php scripts/yaml-validator.php --benchmark

# Output:
# - Files processed per second
# - Memory usage peak
# - Total validation time
```

## Configuration

### Configuration File
```yaml
# scripts/yaml-validator-config.yml
validation:
  required_fields:
    all: ["id", "title", "type", "status", "location"]
    task: ["priority", "effort", "assigned_to"]
    session: ["session_date", "participants", "duration"]
  
  status_values:
    task: ["Draft", "In Progress", "Testing", "Review", "Completed", "Blocked"]
    guide: ["Draft", "Active", "Deprecated", "Archived"]
  
  id_format:
    pattern: "^[A-Z0-9_-]+$"
    max_length: 50
    
  cross_reference:
    require_bidirectional: true
    warn_on_missing_backref: true
    
ignore_patterns:
  - "docs/archive/**"     # Ignore archived documentation
  - "docs/session-logs/archive/**"  # Ignore old session logs
```

## Integration with Development Workflow

### 1. IDE Integration
#### VS Code Extension
- Real-time YAML validation as you type
- Auto-completion for valid field names
- Quick navigation to referenced documents

#### Vim/Emacs Integration
- Syntax highlighting for YAML frontmatter
- Validation on save
- Quick fix commands

### 2. Documentation Generation
- Automatically generate validation reports
- Include validation status in documentation index
- Link validation results to document pages

### 3. Quality Metrics
- Track validation score over time
- Measure documentation quality improvements
- Generate quality dashboards

## Troubleshooting

### Common Issues and Solutions

#### Issue: "YAML parse error"
**Cause**: Invalid YAML syntax
**Solution**: 
- Check indentation (use spaces, not tabs)
- Verify quote matching
- Validate special characters

#### Issue: "Missing required field"
**Cause**: Incomplete YAML frontmatter
**Solution**:
- Run with `--auto-fix` to add defaults
- Check documentation template
- Copy from similar valid document

#### Issue: "Duplicate ID found"
**Cause**: Non-unique document ID
**Solution**:
- Use `--list-ids` to see all IDs
- Update to unique ID following pattern
- Update all references to old ID

### Debug Mode
```bash
# Enable detailed debugging
php scripts/yaml-validator.php --debug --verbose

# Debug specific validation rule
php scripts/yaml-validator.php --debug=required-fields

# Show validation process
php scripts/yaml-validator.php --show-process
```

## Maintenance

### Regular Tasks
1. **Daily**: Run on all changed files (CI/CD)
2. **Weekly**: Full validation with report generation
3. **Monthly**: Review and update validation rules
4. **Quarterly**: Performance optimization and schema updates

### Monitoring
- Track validation success rate
- Monitor common error patterns
- Measure validation performance
- Generate quality trends

---

**Created:** 2025-11-26  
**Author:** AI Agent Roo  
**Version:** 1.0  
**Status:** Ready for Implementation