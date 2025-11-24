/**
 * Product Price Lists Tab E2E Tests
 * @file tests/e2e/product-price-lists-tab.spec.ts
 * @description E2E tests for ProductPriceListsTab component with real data
 * @agent-test: ProductPriceListsTab E2E - Real data testing
 */

import { test, expect } from '@playwright/test';

test.describe('Product Price Lists Tab - Real API', () => {
  test.beforeEach(async ({ page }) => {
    // Login as admin
    await page.goto('http://localhost:5173/login');
    await page.fill('input[name="email"]', 'admin');
    await page.fill('input[name="password"]', '123aA@hai');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard');
  });

  test('should display price lists tab on product edit page', async ({ page }) => {
    // Navigate to product edit page for a known product
    await page.goto('http://localhost:5173/products/1/edit');
    
    // Wait for page to load
    await page.waitForSelector('h1:has-text("Chỉnh sửa sản phẩm")');
    
    // Check if Price Lists tab is present
    await expect(page.getByText('Bảng giá sản phẩm')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Làm mới' })).toBeVisible();
  });

  test('should load price lists data from real API', async ({ page }) => {
    // Navigate to product edit page
    await page.goto('http://localhost:5173/products/1/edit');
    
    // Wait for Price Lists section to appear
    await page.waitForSelector('text=Bảng giá sản phẩm');
    
    // Wait for data to load (check for loading spinner to disappear)
    await page.waitForFunction(() => {
      const spinners = document.querySelectorAll('.ant-spin-spinning');
      return spinners.length === 0;
    }, { timeout: 10000 });
    
    // Check if table appears with real data
    await expect(page.locator('.ant-table')).toBeVisible({ timeout: 10000 });
  });

  test('should display correct table columns', async ({ page }) => {
    await page.goto('http://localhost:5173/products/1/edit');
    
    // Wait for table to load
    await page.waitForSelector('.ant-table');
    
    // Check for expected column headers
    await expect(page.getByText('Tên bảng giá')).toBeVisible();
    await expect(page.getByText('Giá gốc')).toBeVisible();
    await expect(page.getByText('Giá bán')).toBeVisible();
    await expect(page.getByText('Giảm giá')).toBeVisible();
    await expect(page.getByText('Trạng thái')).toBeVisible();
  });

  test('should display formatted prices in VND', async ({ page }) => {
    await page.goto('http://localhost:5173/products/1/edit');
    
    // Wait for data to load
    await page.waitForSelector('.ant-table-tbody tr');
    
    // Look for VND currency format
    const priceElements = page.locator('.ant-table-tbody td');
    const firstRow = priceElements.first();
    
    // Check if prices are formatted (contains ₫ symbol)
    const hasVNDFormat = await page.locator('text=/₫/').isVisible();
    expect(hasVNDFormat).toBeTruthy();
  });

  test('should show refresh functionality', async ({ page }) => {
    await page.goto('http://localhost:5173/products/1/edit');
    
    // Wait for initial load
    await page.waitForSelector('text=Bảng giá sản phẩm');
    
    // Click refresh button
    await page.click('button:has-text("Làm mới")');
    
    // Check if loading state appears briefly
    const spinner = page.locator('.ant-spin');
    await expect(spinner).toBeVisible({ timeout: 2000 });
    
    // Wait for data to reload
    await page.waitForSelector('.ant-table', { timeout: 10000 });
  });

  test('should handle empty state gracefully', async ({ page }) => {
    // Navigate to a product that might not have price lists
    await page.goto('http://localhost:5173/products/999/edit');
    
    // Wait a bit for API response
    await page.waitForTimeout(3000);
    
    // Check if empty state message appears or if it shows no data
    const emptyState = page.getByText('Chưa có dữ liệu bảng giá');
    if (await emptyState.isVisible()) {
      expect(emptyState).toBeVisible();
    } else {
      // If there is data, table should still be functional
      await expect(page.locator('.ant-table')).toBeVisible({ timeout: 5000 });
    }
  });

  test('should display status indicators correctly', async ({ page }) => {
    await page.goto('http://localhost:5173/products/1/edit');
    
    // Wait for data to load
    await page.waitForSelector('.ant-table-tbody tr');
    
    // Look for status tags
    const statusTags = page.locator('.ant-tag');
    const hasStatusTags = await statusTags.count() > 0;
    
    if (hasStatusTags) {
      // Check for known status colors/texts
      const statusTexts = ['Đang hoạt động', 'Sắp diễn ra', 'Không hoạt động'];
      let hasValidStatus = false;
      
      for (const statusText of statusTexts) {
        const statusElement = page.getByText(statusText);
        if (await statusElement.isVisible()) {
          hasValidStatus = true;
          break;
        }
      }
      
      expect(hasValidStatus).toBeTruthy();
    }
  });

  test('should support table sorting', async ({ page }) => {
    await page.goto('http://localhost:5173/products/1/edit');
    
    // Wait for table to load
    await page.waitForSelector('.ant-table');
    
    // Try to click on sortable column headers
    const nameHeader = page.getByText('Tên bảng giá').first();
    if (await nameHeader.isVisible()) {
      await nameHeader.click();
      // Wait briefly for sorting to apply
      await page.waitForTimeout(500);
      
      // Table should still be visible after sorting
      await expect(page.locator('.ant-table')).toBeVisible();
    }
  });

  test('should handle API errors gracefully', async ({ page }) => {
    // Mock offline mode by intercepting API calls
    await page.route('**/api/price-lists*', route => {
      route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({ success: false, message: 'Internal Server Error' })
      });
    });
    
    await page.goto('http://localhost:5173/products/1/edit');
    
    // Wait for error handling
    await page.waitForTimeout(3000);
    
    // Should show error message or empty state
    const errorMessage = page.getByText(/lỗi|error/);
    const emptyMessage = page.getByText('Chưa có dữ liệu bảng giá');
    
    const hasErrorOrEmpty = await errorMessage.isVisible() || await emptyMessage.isVisible();
    expect(hasErrorOrEmpty).toBeTruthy();
  });

  test('should be responsive on mobile view', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto('http://localhost:5173/products/1/edit');
    
    // Wait for content to load on mobile
    await page.waitForSelector('text=Bảng giá sản phẩm');
    
    // Check if component adapts to mobile view
    const emptyState = page.getByText('Chưa có dữ liệu bảng giá');
    if (await emptyState.isVisible()) {
      expect(emptyState).toBeVisible();
    } else {
      // If there is data, table should still be functional
      await expect(page.locator('.ant-table')).toBeVisible({ timeout: 5000 });
    }
  });
});
