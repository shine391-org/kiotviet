# 🧪 Testing Guide - KiotViet CRM

> Quick reference for running tests. For detailed guides, see [docs/testing/](docs/testing/)

## Quick Start

### Backend Tests (MySQL)

```bash
# All unit tests
docker exec kiotviet-web-1 vendor/bin/phpunit

# Specific test file
docker exec kiotviet-web-1 vendor/bin/phpunit tests/Services/ProductServiceTest.php

# With coverage
docker exec kiotviet-web-1 vendor/bin/phpunit --coverage-text

# Integration tests
docker exec kiotviet-web-1 vendor/bin/phpunit -c phpunit.integration.xml
```

### Frontend Tests (Vitest + Playwright)

```bash
cd lanocrm

# Unit/Integration tests
npm test

# Coverage report
npm run test:coverage

# E2E tests (Playwright)
npm run test:e2e

# E2E with UI
npm run test:e2e -- --ui
```

## Test Requirements

- ✅ **Coverage ≥ 70%** for all new code
- ✅ **All tests must pass** before commit
- ✅ **Integration tests** for API endpoints
- ✅ **E2E tests** for critical user flows

## Documentation

- **[Backend Testing Guide](docs/testing/BACKEND-TESTING.md)** - Complete MySQL testing guide
- **[Frontend Testing Guide](docs/testing/FRONTEND-TESTING.md)** - React/Vitest/Playwright guide
- **[Test Checklist](docs/testing/TEST-CHECKLIST.md)** - Mandatory checklist before PR

## Common Issues

### Backend: "Connection refused"
```bash
# Start MySQL container
docker-compose up -d db

# Verify connection
docker exec kiotviet-web-1 php spark db:info tests
```

### Frontend: Tests fail in CI
```bash
# Install Playwright browsers (one-time)
npx playwright install

# Install system dependencies
sudo npx playwright install-deps
```

### Coverage too low
```bash
# Check which files need tests
npm run test:coverage

# Open HTML report
open coverage/index.html
```

---

**For detailed patterns and examples, see the complete guides in [docs/testing/](docs/testing/)**
