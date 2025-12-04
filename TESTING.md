# 🧪 Testing Guide - KiotViet CRM

> Quick reference for running tests. For detailed guides, see [docs/testing/](docs/testing/)

## 🚨 CRITICAL TESTING RULES

### Backend Testing - Test Database Only
- **MUST use `lanocrm_test` database ONLY** - never touch `lanocrm_dev`
- **CANNOT DROP/ALTER tables** - only reset data with TRUNCATE/DELETE
- **CANNOT modify schema** - no table structure changes, FK changes, or index modifications
- **MUST use DevDatabaseTrait** - automatic transaction rollback for data protection

### Frontend Testing - Real Database Integration
- **MUST use real API calls** - NEVER mock API responses
- **CANNOT use fake data** - always call actual backend endpoints
- **MUST verify real database** - test data flow from UI to database
- **CANNOT use MSW/vi.mock** - no API interception or mocking

### No Artificial Test Passing (CRITICAL)
- **NEVER modify tests to pass falsely** - fix implementation instead
- **CANNOT remove assertions** to make tests pass
- **CANNOT change expected values** to match broken implementations
- **CANNOT mock APIs/database** to hide real bugs
- **MUST fix implementation** when tests fail, not the tests
- **See [Testing Rules](docs/testing/TESTING-RULES.md) for details**

### WSL Support
- **Playwright on WSL** may require admin privileges for browser installation
- **Network issues** on WSL may require additional package installation
- **Permission issues** can be resolved with sudo commands
- **See [Testing Rules](docs/testing/TESTING-RULES.md) for WSL troubleshooting**

## Quick Start

### Backend Tests (Test Database Only)

```bash
# Start test database
docker-compose up -d db-test

# All unit tests (test database only)
docker exec meomeo2-api-1 vendor/bin/phpunit

# Performance tests only
docker exec meomeo2-api-1 vendor/bin/phpunit --group performance

# Specific test file
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/ProductServiceTest.php

# With coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text

# Integration tests (test database only)
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml

# Performance integration tests
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml --group performance

# Check test database connection
docker exec meomeo2-api-1 php spark db:info tests
```

### Frontend Tests (Real Database Integration)

```bash
cd lanocrm

# Start backend for frontend tests
docker-compose up -d db-test api

# Unit/Integration tests (real database)
npm test

# Coverage report
npm run test:coverage

# E2E tests (real database)
npm run test:e2e

# Primary E2E command (example)
npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts

# E2E with UI
npm run test:e2e -- --ui

# E2E with specific browser
npm run test:e2e -- --project=firefox
npm run test:e2e -- --project=webkit

# E2E with timeout override
npm run test:e2e -- --timeout=60000
```

### Full Stack Tests

```bash
# Start complete test environment
docker-compose up -d db-test api frontend

# Run all tests (backend + frontend)
npm run test:all
```

## Test Requirements

### Backend Requirements
- ✅ **Test Database Only**: Always use `lanocrm_test`, never `lanocrm_dev`
- ✅ **No Schema Modifications**: Cannot DROP/ALTER tables, change FK, or modify indexes
- ✅ **Data Reset Only**: Use TRUNCATE/DELETE for data cleanup, never schema changes
- ✅ **Coverage ≥ 70%** for all backend code
- ✅ **All tests must pass** before commit
- ✅ **Integration tests** for API endpoints with test database
- ✅ **DevDatabaseTrait Required**: Automatic transaction rollback mandatory

### Frontend Requirements
- ✅ **Real Database Integration**: Must call actual backend APIs
- ✅ **No API Mocking**: Never mock API calls or responses
- ✅ **No Fake Data**: Always use real data from backend
- ✅ **Coverage ≥ 70%** for all frontend code
- ✅ **E2E Tests** for critical user flows with real backend
- ✅ **End-to-End Validation**: Test complete UI to database flow

### No Artificial Passing Requirements
- ✅ **Fix Implementation**: Always fix code when tests fail
- ✅ **Real Assertions**: Test actual behavior, not fake passing
- ✅ **No Test Modifications**: Never change tests to pass falsely
- ✅ **Proper Error Testing**: Test real error conditions
- ✅ **Accurate Expectations**: Expected values must match requirements

### WSL Requirements
- ✅ **Browser Installation**: Playwright browsers properly installed
- ✅ **Permission Setup**: Proper file permissions for test execution
- ✅ **Network Connectivity**: Tests can connect to backend services
- ✅ **Display Configuration**: Proper X11/display setup if needed

### Combined Requirements
- ✅ **Test Data Isolation**: Tests must not affect dev data
- ✅ **Real Error Testing**: Test actual API errors, not mocked ones
- ✅ **Performance Standards**: Backend < 500ms, Frontend < 2s API response
- ✅ **Pre-commit Checks**: All tests must pass automated validation

## Documentation

- **[Testing Rules](docs/testing/TESTING-RULES.md)** - Comprehensive testing rules and guidelines
- **[Backend Testing Guide](docs/testing/BACKEND-TESTING.md)** - Test database only guide
- **[Frontend Testing Guide](docs/testing/FRONTEND-TESTING.md)** - Real database integration guide
- **[Frontend Testing Patterns](docs/testing/FRONTEND-TESTING-PATTERNS.md)** - Real database testing patterns
- **[Performance Testing Guidelines](docs/testing/PERFORMANCE-TESTING-GUIDELINES.md)** - Performance testing standards and benchmarks
- **[Test Checklist](docs/testing/TEST-CHECKLIST.md)** - Mandatory checklist for backend & frontend
- **[Playwright WSL Guide](docs/testing/PLAYWRIGHT-WSL-GUIDE.md)** - Playwright configuration and troubleshooting for WSL

## Common Issues

### Backend: "Test database connection refused"
```bash
# Start test database container
docker-compose up -d db-test

# Verify test database connection
docker exec meomeo2-api-1 php spark db:info tests

# Check test database is ready
docker exec db-test mysql -u root -p -e "SHOW DATABASES;"
```

### Backend: "Table doesn't exist"
```bash
# Check if migrations ran on test database
docker exec meomeo2-api-1 php spark db:status tests

# Run migrations on test database
docker exec meomeo2-api-1 php spark migrate --all --env=testing
```

### Frontend: "API connection refused"
```bash
# Start backend for frontend tests
docker-compose up -d db-test api

# Verify API is accessible
curl http://localhost:8080/api/health

# Check frontend API configuration
echo $VITE_API_BASE_URL
```

### Frontend: "Tests fail with mocked APIs"
```bash
# Check for API mocking in test files
grep -r "vi.mock\|MSW\|mock(" lanocrm/src/tests/

# Remove all mocking patterns
# Follow real database integration patterns
```

### Frontend: "Artificial test passing detected"
```bash
# Check for artificial passing patterns
grep -r "assertTrue(true)\|expect(true).toBe(true)" lanocrm/src/tests/
grep -r "markTestSkipped\|it.skip" lanocrm/src/tests/

# Fix implementation instead of tests
# Follow real testing patterns
```

### WSL: "Playwright browser installation fails"
```bash
# Install browsers with admin rights
sudo npx playwright install --with-deps chromium

# Install required system packages
sudo apt-get update
sudo apt-get install -y wget ca-certificates fonts-liberation libasound2 libatk-bridge2.0-0 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc1 libglib2.0-0 libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 lsb-release xdg-utils
```

### WSL: "Permission denied errors"
```bash
# Fix Playwright permissions
sudo chmod -R 755 ~/.cache/ms-playwright

# Run tests with admin rights if needed
sudo npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts
```

### Coverage too low
```bash
# Check backend coverage
docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-text

# Check frontend coverage
cd lanocrm && npm run test:coverage

# Open HTML reports
open coverage/index.html
open lanocrm/coverage/index.html
```

### Frontend: "E2E tests timeout"
```bash
# Increase timeout for real API calls
# npx playwright test --timeout=60000

# Check backend performance
docker exec meomeo2-api-1 php spark db:info tests
```

### WSL: "E2E tests timeout or hang"
```bash
# Increase timeout for WSL environment
npm run test:e2e -- --timeout=60000

# Check network connectivity
ping localhost
ping 127.0.0.1

# If network issues persist, contact admin for package installation
```

---

## Quick E2E Command Reference

```bash
# Primary E2E command (example)
cd ~/projects/kiotviet/lanocrm
npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts

# All E2E tests
npm run test:e2e

# With UI for debugging
npm run test:e2e -- --ui

# WSL with admin rights (if needed)
sudo npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts
```

**For detailed patterns and examples, see the complete guides in [docs/testing/](docs/testing/)**
