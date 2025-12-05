---
title: "Frontend Testing Guide - Real Database Integration"
id: "FRONTEND-TESTING-01"
version: "4.0"
status: "Active"
module: "Frontend Testing"
type: "Guideline"
tags: ["testing", "frontend", "react", "vitest", "playwright", "real-database", "no-mocking", "no-artificial-passing", "wsl"]
purpose: "Provides comprehensive guide for frontend testing using real database integration, with strict prohibition of API mocking and artificial test passing."
location: "docs/testing"
updated: "2025-12-03"
changes: "Consolidated from FRONTEND-TESTING-PATTERNS.md (empty file), updated with comprehensive patterns, WSL support, and E2E guidelines"
related_to:
  - id: "BACKEND-TESTING-01"
    description: "Backend testing guide with test database only"
  - id: "TEST-CHECKLIST-01"
    description: "Mandatory checklist for all frontend changes"
  - id: "TESTING-RULES-01"
    description: "Comprehensive testing rules and guidelines"
  - id: "PLAYWRIGHT-WSL-01"
    description: "Playwright WSL configuration and troubleshooting guide"
---

# Frontend Testing Guide - Real Database Integration

> **🚨 CRITICAL RULES**:
> 1. Frontend tests MUST use real database integration. **NEVER** mock API calls or use fake data.
> 2. **NO ARTIFICIAL TEST PASSING**: Never modify tests to pass falsely. Fix implementation instead. See [Testing Rules](TESTING-RULES.md) for details.

## Testing Philosophy

### Real Database Integration Only
- **Real API Calls**: All tests must call actual backend APIs
- **Real Database**: Tests interact with `lanocrm_test` database through APIs
- **No Mocking**: Never mock API responses or use fake data
- **End-to-End Validation**: Test complete data flow from UI to database

### Why Real Database Integration?
1. **Realistic Testing**: Tests actual API behavior and database constraints
2. **Integration Validation**: Ensures frontend works with real backend
3. **Data Integrity**: Validates data flow from UI to database
4. **Bug Detection**: Catches integration issues that mocking would hide

## 🚫 No Artificial Test Passing (CRITICAL)

**Definition**: "Pass ảo" là cố tình sửa bài test nhằm gây kết quả giả, không phản ánh đúng thực tế chức năng.

### ❌ FORBIDDEN - Examples of Artificial Passing:

```javascript
// BAD - Removing assertions to make tests pass
test('product form validation', async () => {
  render(<ProductForm />);
  await userEvent.click(screen.getByRole('button', { name: 'Submit' }));
  // REMOVED: expect(screen.getByText('Name is required')).toBeInTheDocument();
  expect(true).toBe(true); // Always passes!
});

// BAD - Mocking API to hide backend issues
test('creates product successfully', async () => {
  // Mocking API to hide actual backend problems
  vi.mock('../api/productApi', () => ({
    createProduct: vi.fn(() => Promise.resolve({ success: true }))
  }));
  
  const result = await createProduct(invalidData);
  expect(result.success).toBe(true); // Passes despite invalid data
});

// BAD - Changing test expectations to match bugs
test('calculates total price correctly', async () => {
  const result = calculateTotal(items);
  // Changed expectation to match buggy calculation
  expect(result).toBe(150); // Should be 200, but changed to match bug
});
```

### ✅ REQUIRED - Correct Testing Approach:

```javascript
// GOOD - Test real user interactions and API responses
test('product form validation', async () => {
  render(<ProductForm />);
  await userEvent.click(screen.getByRole('button', { name: 'Submit' }));
  
  // Wait for real validation error
  await waitFor(() => {
    expect(screen.getByText('Name is required')).toBeInTheDocument();
  });
});

// GOOD - Test with real API calls
test('creates product successfully', async () => {
  // Reset test database
  await resetTestDatabase();
  
  const result = await createProduct(validData);
  expect(result.success).toBe(true);
  
  // Verify in real database
  const product = await getProductFromDatabase(result.data.id);
  expect(product.name).toBe(validData.name);
});

// GOOD - Test actual requirements, not current buggy behavior
test('calculates total price correctly', async () => {
  const result = calculateTotal(items);
  expect(result).toBe(200); // Correct expected value
  // If this fails, fix implementation, not test
});
```

## Required Testing Pattern

### Real Database Integration (MANDATORY)

All frontend tests MUST follow this pattern:

```javascript
// 1. Setup test database with real data
beforeEach(async () => {
  // Clean test database
  await resetTestDatabase();
  
  // Seed test data
  await seedTestData();
});

// 2. Make real API calls (no mocking)
test('creates product with real API', async () => {
  render(<ProductForm />);
  
  // Fill form
  await userEvent.type(screen.getByLabelText('Mã hàng'), 'TEST-001');
  await userEvent.type(screen.getByLabelText('Tên sản phẩm'), 'Test Product');
  
  // Submit form - REAL API CALL
  await userEvent.click(screen.getByRole('button', { name: /Thêm sản phẩm/i }));
  
  // Verify in REAL database
  await waitFor(() => {
    expect(screen.getByText('✅ Tạo mới sản phẩm thành công')).toBeInTheDocument();
  });
  
  // Verify data in test database
  const product = await getProductFromDatabase('TEST-001');
  expect(product).toBeTruthy();
  expect(product.name).toBe('Test Product');
});
```

### Test Database Setup for Frontend

```javascript
// test-setup.js
import { setupTestDatabase } from './test-helpers/database';

beforeAll(async () => {
  // Setup test database connection
  await setupTestDatabase();
});

beforeEach(async () => {
  // Reset test database to clean state
  await resetTestDatabase();
  
  // Seed minimal test data
  await seedMinimalTestData();
});
```

## Test Types & Patterns

### 1. Unit Tests (Components, Hooks, Utils)

**Purpose**: Test component logic with real data
**Database**: Test database through API calls
**Speed**: Medium (real API calls)

```javascript
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ProductForm } from '../ProductForm';
import { createTestProduct, resetTestDatabase } from '../../test-helpers/database';

describe('ProductForm Component', () => {
  beforeEach(async () => {
    await resetTestDatabase();
  });

  test('creates product with real database', async () => {
    render(<ProductForm />);
    
    await userEvent.type(screen.getByLabelText('Mã hàng'), 'REAL-001');
    await userEvent.type(screen.getByLabelText('Tên sản phẩm'), 'Real Product');
    await userEvent.click(screen.getByRole('button', { name: /Thêm sản phẩm/i }));
    
    // Wait for REAL API response
    await waitFor(() => {
      expect(screen.getByText('✅ Tạo mới sản phẩm thành công')).toBeInTheDocument();
    });
    
    // Verify in REAL database
    const product = await getProductFromDatabase('REAL-001');
    expect(product).toBeTruthy();
    expect(product.name).toBe('Real Product');
  });
});
```

### 2. Integration Tests (Pages, Flows)

**Purpose**: Test complete user flows with real backend
**Database**: Test database through full application
**Speed**: Slow (full stack)

```javascript
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { ProductListPage } from '../ProductListPage';
import { setupTestUser, resetTestDatabase, seedProducts } from '../../test-helpers/database';

describe('Product List Integration', () => {
  beforeEach(async () => {
    await resetTestDatabase();
    await setupTestUser();
    await seedProducts([
      { code: 'PROD-001', name: 'Product 1', price: 100000 },
      { code: 'PROD-002', name: 'Product 2', price: 200000 }
    ]);
  });

  test('displays products from real database', async () => {
    render(
      <MemoryRouter>
        <ProductListPage />
      </MemoryRouter>
    );
    
    // Wait for REAL API call to complete
    await waitFor(() => {
      expect(screen.getByText('Product 1')).toBeInTheDocument();
      expect(screen.getByText('Product 2')).toBeInTheDocument();
      expect(screen.getByText('100,000 ₫')).toBeInTheDocument();
      expect(screen.getByText('200,000 ₫')).toBeInTheDocument();
    });
  });
});
```

### 3. E2E Tests (Playwright)

**Purpose**: Test complete user journeys with real backend
**Database**: Test database through browser automation
**Speed**: Slowest (real browser + real API)

```typescript
import { test, expect } from '@playwright/test';
import { setupTestDatabase, seedTestData } from '../../test-helpers/database';

test.describe('Product Management E2E', () => {
  test.beforeEach(async () => {
    await setupTestDatabase();
    await seedTestData();
  });

  test('complete product creation flow with real database', async ({ page }) => {
    // Navigate to product creation
    await page.goto('/products/create');
    
    // Fill form with REAL data
    await page.fill('input[name="code"]', 'E2E-001');
    await page.fill('input[name="name"]', 'E2E Product');
    await page.fill('input[name="selling_price"]', '150000');
    
    // Submit form - REAL API CALL
    await page.click('button[type="submit"]');
    
    // Verify success message
    await expect(page.locator('.success-message')).toContainText('✅ Tạo mới sản phẩm thành công');
    
    // Navigate to product list
    await page.goto('/products');
    
    // Verify product in REAL database
    await expect(page.locator('table')).toContainText('E2E-001');
    await expect(page.locator('table')).toContainText('E2E Product');
    await expect(page.locator('table')).toContainText('150,000 ₫');
  });
});
```

## Test Database Helpers

### Database Setup Utilities

```javascript
// test-helpers/database.js
import axios from 'axios';

const TEST_API_BASE = 'http://localhost:8080/api';

export async function resetTestDatabase() {
  // Call backend test database reset endpoint
  await axios.post(`${TEST_API_BASE}/test/reset-database`);
}

export async function seedTestData(data) {
  // Seed test data through API
  await axios.post(`${TEST_API_BASE}/test/seed-data`, data);
}

export async function createTestProduct(productData) {
  // Create product through REAL API
  const response = await axios.post(`${TEST_API_BASE}/products`, productData);
  return response.data;
}

export async function getProductFromDatabase(code) {
  // Get product from REAL database through API
  const response = await axios.get(`${TEST_API_BASE}/products`, {
    params: { search: code }
  });
  return response.data.data.find(p => p.code === code);
}

export async function setupTestUser() {
  // Setup authenticated user for tests
  await axios.post(`${TEST_API_BASE}/test/setup-user`, {
    username: 'testuser',
    permissions: ['products.view', 'products.create', 'products.edit']
  });
}
```

### Test Data Management

```javascript
// test-data/products.js
export const TEST_PRODUCTS = [
  {
    code: 'TEST-001',
    name: 'Test Product 1',
    selling_price: 100000,
    category_id: 1,
    product_type: 'goods'
  },
  {
    code: 'TEST-002',
    name: 'Test Product 2',
    selling_price: 200000,
    category_id: 1,
    product_type: 'goods'
  }
];

export const TEST_CATEGORIES = [
  { id: 1, name: 'Test Category', parent_id: null }
];
```

## Forbidden Operations (CRITICAL)

### ❌ NEVER Do These in Frontend Tests

```javascript
// FORBIDDEN - Never mock API calls
vi.mock('../api/productApi', () => ({
  createProduct: vi.fn(() => Promise.resolve({ success: true }))
}));

// FORBIDDEN - Never use fake data
const mockProducts = [
  { id: 1, name: 'Fake Product', code: 'FAKE-001' }
];

// FORBIDDEN - Never mock axios
vi.mock('axios', () => ({
  post: vi.fn(() => Promise.resolve({ data: { success: true } }))
}));

// FORBIDDEN - Never use MSW to intercept API calls
import { setupServer } from 'msw/node';
const server = setupServer(
  rest.post('/api/products', (req, res, ctx) => {
    return res(ctx.json({ success: true, data: mockProduct }));
  })
);
```

### ✅ ALWAYS Do These Instead

```javascript
// ALLOWED - Use real API calls
import { createProduct } from '../api/productApi';
const result = await createProduct(productData);

// ALLOWED - Use real database helpers
import { resetTestDatabase, seedTestData } from '../test-helpers/database';
await resetTestDatabase();
await seedTestData(TEST_PRODUCTS);

// ALLOWED - Test with real test data
import { TEST_PRODUCTS } from '../test-data/products';
await seedTestData(TEST_PRODUCTS);

// ALLOWED - Verify in real database
const product = await getProductFromDatabase('TEST-001');
expect(product).toBeTruthy();
```

## Running Frontend Tests

### Unit/Integration Tests

```bash
cd lanocrm

# Start backend test server
docker-compose up -d db-test api

# Run unit tests with real database
npm test

# Run with coverage
npm run test:coverage

# Run specific test file
npm test ProductForm.test.jsx
```

### E2E Tests

```bash
cd lanocrm

# Start full stack (backend + frontend)
docker-compose up -d db-test api frontend

# Primary E2E testing command (example)
npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts

# Run all E2E tests with real database
npm run test:e2e

# Run with specific browser
npm run test:e2e -- --project=firefox
npm run test:e2e -- --project=webkit

# Run with UI for debugging
npm run test:e2e -- --ui

# Run with headed mode (show browser)
npm run test:e2e -- --headed

# Run with timeout override
npm run test:e2e -- --timeout=60000
```

## Test Environment Setup

### Backend Test Server

```bash
# Start test database
docker-compose up -d db-test

# Start backend API server
docker-compose up -d api

# Verify API is accessible
curl http://localhost:8080/api/health
```

### Frontend Test Environment

```bash
# Start frontend development server
cd lanocrm
npm run dev

# Configure to use test backend
export VITE_API_BASE_URL=http://localhost:8080/api
```

## Coverage Requirements

- **Minimum Coverage**: 70% statements, branches, functions, lines
- **Critical Components**: 90%+ coverage
- **API Integration**: 85%+ coverage
- **E2E Flows**: 80%+ coverage

### Coverage Commands

```bash
# Check coverage
npm run test:coverage

# Generate HTML report
open coverage/index.html

# Check specific file coverage
npm run test:coverage -- ProductForm.test.jsx
```

## Troubleshooting

### Common Issues

#### "API connection refused"
```bash
# Start backend test server
docker-compose up -d api

# Check API is running
curl http://localhost:8080/api/health

# Check frontend API configuration
echo $VITE_API_BASE_URL
```

#### "Database connection failed"
```bash
# Check test database is running
docker-compose ps db-test

# Verify test database connection
docker exec kiotviet-web-1 php spark db:info tests
```

#### "Test data not found"
```bash
# Reset test database
npm run test:reset-db

# Seed test data
npm run test:seed-data
```

## Best Practices

### Test Organization
- One test file per component/page
- Descriptive test names
- Real data scenarios
- End-to-end validation

### Data Management
- Use consistent test data
- Clean up between tests
- Realistic data volumes
- Proper data relationships

### Error Handling
- Test API error responses
- Test network failures
- Test validation errors
- Test edge cases

### Performance Testing
- Test with realistic data
- Monitor API response times
- Check UI performance
- Test concurrent operations

## WSL-Specific Issues

### Common Problems on WSL

1. **Browser Installation Issues**:
```bash
# If browsers fail to install on WSL
npx playwright install --with-deps chromium

# If permission issues occur
sudo npx playwright install --with-deps chromium

# If network issues occur
sudo apt-get update
sudo apt-get install -y wget ca-certificates fonts-liberation libasound2 libatk-bridge2.0-0 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc1 libglib2.0-0 libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 lsb-release xdg-utils
```

2. **Permission Issues**:
```bash
# If you get permission denied errors
sudo chmod -R 755 ~/.cache/ms-playwright

# If you need admin privileges for installation
sudo npx playwright install-deps
```

3. **Network Connection Issues**:
```bash
# If tests can't connect to backend
ping localhost  # Test local connectivity
ping 127.0.0.1  # Test loopback
# If ping fails, contact admin to install network packages
```

4. **Display/X11 Issues**:
```bash
# If you get display errors on WSL
export DISPLAY=:0
# Or install X11 forwarding
sudo apt-get install x11-apps
```

### WSL E2E Test Commands

```bash
# On WSL, ensure proper permissions
cd ~/projects/kiotviet/lanocrm

# Run E2E with proper environment
npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts

# If tests hang, increase timeout
npm run test:e2e -- --timeout=60000

# If you get permission errors
sudo npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts
```

---

## Summary

**Golden Rules for Frontend Testing:**

1. **Real Database Only**: Always use real API calls to `lanocrm_test`
2. **No API Mocking**: Never mock API responses or use fake data
3. **No Artificial Passing**: Never modify tests to pass falsely. Fix implementation instead.
4. **End-to-End Validation**: Test complete data flow from UI to database
5. **Real Test Data**: Use realistic test data scenarios
6. **Integration Coverage**: Maintain 70%+ coverage across all components
7. **WSL Compatibility**: Ensure tests work properly on WSL environments

**Remember**: Frontend tests should validate real integration with backend, not isolated component behavior. Always fix implementation issues, never modify tests to pass falsely.
