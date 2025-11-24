---
title: "Testing Guide - LANO CRM Backend"
id: "TESTING-GUIDE-01"
version: "1.0"
status: "Active"
module: "Testing"
type: "Guideline"
tags: ["testing", "backend", "unit-tests", "integration-tests", "test-pyramid", "ci-cd"]
purpose: "Provides a comprehensive guide to the backend testing strategy, process, environment setup, and requirements for LANO CRM."
location: "docs/testing"
related_to:
  - id: "TESTING-PATTERNS-01"
    description: "Refer to this for code examples and patterns to copy."
---

# Testing Guide

## Section 1: Test Pyramid
We follow the standard Test Pyramid to ensure a balanced and efficient testing strategy:

- **60% Unit Tests**: Fast, isolated tests for Services, Repositories, and Helpers. Use SQLite in-memory.
- **30% Integration Tests**: Tests involving the database (MySQL) and API endpoints. Slower but ensure components work together.
- **10% E2E Tests**: Critical user flows (Login, Checkout). (Currently manual or future automation).

### Trade-offs
| Type | Speed | Cost | Confidence | Scope |
|------|-------|------|------------|-------|
| Unit | Fast | Low | Low (Component only) | Single Class/Method |
| Integration | Medium | Medium | Medium (System) | Multiple Components + DB |
| E2E | Slow | High | High (User) | Full Application |

## Section 2: Mandatory Testing Process

### Local Development (Every Commit)
1. **Write Test FIRST (TDD)**: Define behavior before implementation.
2. **Run Unit Tests**: `vendor/bin/phpunit`
3. **Run Integration Tests**: If modifying API or Database layers.

### Pre-commit (Mandatory)
1. All unit tests must pass.
2. Integration tests must pass (if applicable).
3. Run Safety Checks: `bash .ai/pre-commit-checks.sh`

### Pre-merge (Critical)
1. Full test suite pass.
2. Coverage >= 70% for modified files.
3. Manual smoke test on dev server.

## Section 3: Test Environment Setup

### Unit Tests (SQLite)
- **Setup**: Zero setup required. Uses in-memory SQLite.
- **Config**: Configured in `phpunit.xml`.

### Integration Tests (MySQL)
- **Setup**: Requires a running MySQL test container.
- **Command**: `docker-compose up -d db-test`
- **Config**: `backend-ci/phpunit.integration.xml`

### Switching Environments
- **Unit**: Default `phpunit`
- **Integration**: `phpunit -c backend-ci/phpunit.integration.xml`

## Section 4: Common Issues & Solutions

### ISSUE 1: PHPUnit passes but dev server fails
**Cause**: SQLite allows loose syntax that MySQL rejects (e.g., date formats, group by).
**Solution**: Always run integration tests with real MySQL.
**Example**:
```bash
# Run integration tests
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml
```

### ISSUE 2: Tests pass individually but fail together
**Cause**: Database pollution. Previous tests didn't clean up data.
**Solution**: Use Transactions and proper `tearDown`.
**Example**:
```php
protected function setUp(): void {
    parent::setUp();
    $this->db->transBegin();
}

protected function tearDown(): void {
    $this->db->transRollback();
    parent::tearDown();
}
```

### ISSUE 3: Slow tests
**Cause**: Re-creating the schema for every single test method.
**Solution**: Reuse schema, only truncate tables or use transactions.

## Section 5: Coverage Requirements
- **Minimum 70%** for all new Services and Repositories.
- **100%** for critical business logic (e.g., Calculations, Permissions).
- **Tools**: Use Xdebug with PHPUnit for coverage reports.

## Section 6: CI/CD Integration
- Automated tests run on every PR.
- Pre-merge hooks block commits if tests fail.
- Test reports generated in `build/logs/`.
