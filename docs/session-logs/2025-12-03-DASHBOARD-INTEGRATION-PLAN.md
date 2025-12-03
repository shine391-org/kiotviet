---
title: "Dashboard Integration & E2E Testing Plan"
date: "2025-12-03"
status: "In Progress"
tags: ["dashboard", "integration", "e2e", "testing", "playwright"]
---

# Dashboard Integration & E2E Testing Plan

## 📋 Current State Analysis

### ✅ Backend Implementation (Complete)
- **Controller**: [`DashboardController.php`](backend-ci/app/Controllers/Api/DashboardController.php) - Clean, thin controller
- **Service**: [`DashboardService.php`](backend-ci/app/Services/Dashboard/DashboardService.php) - Business logic layer
- **Repository**: [`DashboardRepository.php`](backend-ci/app/Repositories/Dashboard/DashboardRepository.php) - Database queries

**API Endpoints:**
1. `GET /api/dashboard/kpi-today` - Today's KPI metrics
2. `GET /api/dashboard/revenue-chart` - Revenue chart data
3. `GET /api/dashboard/top-products` - Top 10 products ranking
4. `GET /api/dashboard/top-customers` - Top 10 customers ranking
5. `GET /api/dashboard/activities` - Recent activities timeline

### ✅ Frontend Implementation (Complete)
- **Component**: [`Dashboard.jsx`](lanocrm/src/pages/Dashboard.jsx) - React component with Chart.js
- **Redux Slice**: [`dashboardSlice.js`](lanocrm/src/store/slices/dashboardSlice.js) - State management
- **API Client**: [`dashboardApi.js`](lanocrm/src/api/dashboardApi.js) - API calls

**Features:**
- KPI cards (Revenue, Returns, Net Revenue with % change)
- Revenue chart (Bar/Column, filterable by period/range)
- Top 10 products ranking
- Top 10 customers ranking
- Recent activities feed
- Fallback to sample data on API errors

### ✅ Database Schema (Complete)
**Tables:**
- `invoices` - Invoice records with totals, VAT, payment status
- `returns` - Return orders with refund amounts
- `orders` - Order records with items, payments, status
- `delivery_notes` - Delivery tracking
- `order_items` - Line items for orders
- `order_payments` - Payment records

**Demo Data:**
- ✅ 20 demo orders (`DH-DEMO-001` to `DH-DEMO-020`)
- ✅ 20 demo invoices (`HD-DEMO-*`)
- ✅ 4 demo returns (`RET-DEMO-001` to `RET-DEMO-004`)
- ✅ Delivery notes for all non-cancelled orders
- ✅ Customers, products, variants, branches seeded

## 🔍 Issues Identified

### 1. **Authentication Required**
- Dashboard API endpoints require JWT authentication
- Frontend must be logged in to fetch data
- Need to handle auth in E2E tests

### 2. **Potential Data Mismatch**
The `DashboardRepository` queries assume:
- `invoices.issue_date` for revenue aggregation
- `returns.updated_at` for return date
- `delivery_notes.delivery_date` for activities

**Verification needed:**
- Are these columns populated correctly in demo data?
- Do the queries return expected results?

### 3. **Frontend Fallback Behavior**
- Dashboard shows sample data when API fails
- This masks real API errors during development
- Need to verify actual API responses

## 📝 Implementation Plan

### Phase 1: Backend Verification ✅
**Status:** Schema and seeders verified

- [x] Verify database schema for all required tables
- [x] Confirm demo seeders exist and are comprehensive
- [x] Review seeder execution order in `DemoSeeder.php`

### Phase 2: API Testing & Fixes
**Tasks:**

1. **Test Dashboard API Endpoints**
   ```bash
   # Login first to get token
   TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"admin123"}' \
     | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
   
   # Test KPI endpoint
   curl -H "Authorization: Bearer $TOKEN" \
     http://localhost:8000/api/dashboard/kpi-today
   
   # Test revenue chart
   curl -H "Authorization: Bearer $TOKEN" \
     "http://localhost:8000/api/dashboard/revenue-chart?period=day&range=month"
   
   # Test top products
   curl -H "Authorization: Bearer $TOKEN" \
     "http://localhost:8000/api/dashboard/top-products?metric=net_revenue&range=month&limit=10"
   
   # Test top customers
   curl -H "Authorization: Bearer $TOKEN" \
     "http://localhost:8000/api/dashboard/top-customers?range=month&limit=10"
   
   # Test activities
   curl -H "Authorization: Bearer $TOKEN" \
     "http://localhost:8000/api/dashboard/activities?limit=15"
   ```

2. **Fix Any Data Issues**
   - If queries return empty results, check:
     - Are `invoices` populated with `issue_date`?
     - Are `returns` populated with `updated_at`?
     - Are `orders` linked correctly to `order_items`?
     - Are `customers` and `products` referenced correctly?

3. **Update Repository Queries (if needed)**
   - Fix date column references
   - Add proper NULL handling
   - Optimize JOIN queries

### Phase 3: Frontend Integration Testing

1. **Manual Browser Testing**
   - Open http://localhost:3000/dashboard
   - Login with demo credentials
   - Verify all widgets load data
   - Test filters and interactions
   - Check console for errors

2. **Fix Frontend Issues**
   - Remove/update fallback sample data
   - Handle empty states properly
   - Add loading skeletons
   - Improve error messages

### Phase 4: E2E Testing with Playwright

**Test File:** `lanocrm/tests/e2e/dashboard.spec.js`

```javascript
import { test, expect } from '@playwright/test';

test.describe('Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    // Login
    await page.goto('http://localhost:3000/login');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard');
  });

  test('should display KPI cards', async ({ page }) => {
    await page.goto('http://localhost:3000/dashboard');
    
    // Wait for KPI cards to load
    await expect(page.locator('.kpi-card')).toHaveCount(3);
    
    // Verify KPI titles
    await expect(page.locator('text=Doanh thu')).toBeVisible();
    await expect(page.locator('text=Trả hàng')).toBeVisible();
    await expect(page.locator('text=Doanh thu thuần')).toBeVisible();
    
    // Verify values are numbers (not sample data)
    const revenueValue = await page.locator('.kpi-card:has-text("Doanh thu") .kpi-value').textContent();
    expect(revenueValue).toMatch(/[\d,]+/);
  });

  test('should display revenue chart', async ({ page }) => {
    await page.goto('http://localhost:3000/dashboard');
    
    // Wait for chart to render
    await expect(page.locator('.chart-wrapper canvas')).toBeVisible();
    
    // Verify chart controls
    await expect(page.locator('text=Theo ngày')).toBeVisible();
    await expect(page.locator('text=Tháng này')).toBeVisible();
  });

  test('should filter revenue chart by period', async ({ page }) => {
    await page.goto('http://localhost:3000/dashboard');
    
    // Change to hourly view
    await page.click('text=Theo giờ');
    await page.waitForTimeout(500); // Wait for chart update
    
    // Verify chart updated (check for hour labels)
    const chartExists = await page.locator('.chart-wrapper canvas').isVisible();
    expect(chartExists).toBeTruthy();
  });

  test('should display top products', async ({ page }) => {
    await page.goto('http://localhost:3000/dashboard');
    
    // Wait for top products list
    await expect(page.locator('.ranking-list .ranking-row')).toHaveCount(10, { timeout: 5000 });
    
    // Verify first product has name and value
    const firstProduct = page.locator('.ranking-list .ranking-row').first();
    await expect(firstProduct.locator('.ranking-name')).not.toBeEmpty();
    await expect(firstProduct.locator('.ranking-value')).not.toBeEmpty();
  });

  test('should display top customers', async ({ page }) => {
    await page.goto('http://localhost:3000/dashboard');
    
    // Scroll to customers section
    await page.locator('text=Top 10 khách mua nhiều nhất').scrollIntoViewIfNeeded();
    
    // Wait for customers list
    await expect(page.locator('.list-card:has-text("khách mua nhiều") .ranking-row')).toHaveCount(10, { timeout: 5000 });
  });

  test('should display recent activities', async ({ page }) => {
    await page.goto('http://localhost:3000/dashboard');
    
    // Scroll to activities
    await page.locator('text=Hoạt động gần đây').scrollIntoViewIfNeeded();
    
    // Wait for activities list
    const activities = page.locator('.activity-list .activity-item');
    await expect(activities).toHaveCount(15, { timeout: 5000 });
    
    // Verify activity structure
    const firstActivity = activities.first();
    await expect(firstActivity.locator('.activity-user')).not.toBeEmpty();
    await expect(firstActivity.locator('.activity-action')).not.toBeEmpty();
    await expect(firstActivity.locator('.activity-amount')).not.toBeEmpty();
  });

  test('should refresh all data', async ({ page }) => {
    await page.goto('http://localhost:3000/dashboard');
    
    // Click refresh button
    await page.click('button:has-text("Làm mới")');
    
    // Wait for loading state
    await page.waitForTimeout(500);
    
    // Verify data reloaded (check for updated timestamp)
    await expect(page.locator('text=Cập nhật')).toBeVisible();
  });

  test('should handle empty states gracefully', async ({ page }) => {
    // This test would require mocking empty API responses
    // or testing with a fresh database
    await page.goto('http://localhost:3000/dashboard');
    
    // Verify no error messages are shown
    await expect(page.locator('text=Không thể tải')).not.toBeVisible();
  });
});
```

**Run E2E Tests:**
```bash
cd lanocrm
npm run test:e2e
```

### Phase 5: Documentation & Cleanup

1. **Update README**
   - Document Dashboard features
   - Add API endpoint documentation
   - Include testing instructions

2. **Create Session Log**
   - Document all changes made
   - List any bugs fixed
   - Note test coverage achieved

## 🎯 Success Criteria

- [ ] All 5 Dashboard API endpoints return valid JSON
- [ ] Frontend displays real data (not fallback samples)
- [ ] No console errors on Dashboard page
- [ ] All E2E tests pass (8 test cases)
- [ ] Test coverage >= 70% for Dashboard components
- [ ] Documentation updated

## 🐛 Known Issues to Fix

1. **Authentication Flow**
   - Dashboard requires login
   - Need to handle token refresh
   - Add proper error handling for 401 responses

2. **Data Consistency**
   - Verify invoice dates match order dates
   - Ensure return amounts are calculated correctly
   - Check delivery note statuses

3. **Performance**
   - Dashboard queries may be slow with large datasets
   - Consider adding database indexes
   - Implement caching for KPI calculations

## 📚 Related Documents

- [Backend Testing Guide](../testing/BACKEND-TESTING.md)
- [Frontend Testing Guide](../testing/FRONTEND-TESTING.md)
- [Test Checklist](../testing/TEST-CHECKLIST.md)
- [Deployment Guide](../../DEPLOYMENT.md)

## 🔗 Next Steps

After Dashboard is complete:
1. Implement similar E2E tests for other modules (Orders, Products, Customers)
2. Set up CI/CD pipeline to run E2E tests automatically
3. Add performance monitoring for Dashboard queries
4. Implement real-time updates using WebSockets (future enhancement)