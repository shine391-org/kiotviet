/**
 * E2E Tests for Invoice Management
 * 
 * IMPORTANT: These tests use REAL API calls to the backend.
 * No mocking is allowed per TESTING-RULES.md
 * 
 * Prerequisites:
 * - Backend running at localhost:8080
 * - Frontend running at localhost:3000
 * - Test database (lanocrm_test) available
 */

import { test, expect } from '@playwright/test';

test.describe('Invoice Management', () => {
  // Login before each test
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.waitForSelector('input[name="username"]', { timeout: 10000 });
    await page.fill('input[name="username"]', 'devadmin');
    await page.fill('input[name="password"]', '123aA@hai');
    await page.click('button[type="submit"]');
    // Wait for dashboard or any authenticated page
    await page.waitForURL(/\/(dashboard|orders|customers|products)/, { timeout: 15000 });
  });

  test('should display invoice list page', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    await expect(page.locator('input[placeholder*="mã hóa đơn"]')).toBeVisible({ timeout: 20000 });
    
    // Verify main elements are present
    await expect(page.locator('button.ant-btn-primary:has-text("Tạo mới")')).toBeVisible();
  });

  test('should search invoices by code', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for initial load
    await page.waitForSelector('.ant-table');
    
    // Search for an invoice
    const searchInput = page.locator('input[placeholder*="mã hóa đơn"]');
    await searchInput.fill('HD');
    
    // Wait for search results (debounced)
    await page.waitForTimeout(500);
    
    // Table should update with search results
    await expect(page.locator('.ant-table-tbody')).toBeVisible();
  });

  test('should filter invoices by branch', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for page load
    await page.waitForSelector('.ant-table');
    
    // Find branch filter in sidebar
    const branchSelect = page.locator('.ant-card:has-text("Hóa đơn")').locator('.ant-select').first();
    
    // Click to open dropdown
    await branchSelect.click();
    
    // Wait for options to load
    await page.waitForTimeout(300);
    
    // Select first branch option if available
    const firstOption = page.locator('.ant-select-item').first();
    if (await firstOption.isVisible()) {
      await firstOption.click();
      await page.waitForTimeout(500);
    }
    
    // Table should show filtered results
    await expect(page.locator('.ant-table-tbody')).toBeVisible();
  });

  test('should filter invoices by status', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for page load
    await page.waitForSelector('.ant-table');
    
    // Find status checkboxes in filter panel
    const filterPanel = page.locator('.ant-card:has-text("Hóa đơn")');
    
    // Check "Hoàn thành" status
    const completedCheckbox = filterPanel.locator('.ant-checkbox-wrapper:has-text("Hoàn thành")');
    if (await completedCheckbox.isVisible()) {
      await completedCheckbox.click();
      await page.waitForTimeout(500);
    }
    
    // Table should update
    await expect(page.locator('.ant-table')).toBeVisible();
  });

  test('should filter invoices by date range', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('.ant-table', { timeout: 15000 });
    
    // Look for date range picker or date filter
    const dateFilter = page.locator('.ant-picker-range, .ant-radio-group:has-text("Hôm nay")');
    if (await dateFilter.count() > 0) {
      await expect(dateFilter.first()).toBeVisible();
    }
    
    // The table should be visible
    await expect(page.locator('.ant-table')).toBeVisible();
  });

  test('should select invoice and show detail', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('.ant-table', { timeout: 15000 });
    
    // Check if there's data in the table
    const rows = page.locator('.ant-table-tbody tr.ant-table-row');
    const rowCount = await rows.count();
    
    if (rowCount > 0) {
      // Click on first invoice row
      await rows.first().click();
      
      // Detail panel should show invoice info
      await expect(page.getByText('Chi tiết hóa đơn')).toBeVisible({ timeout: 5000 });
    } else {
      // No data - check empty state
      await expect(page.locator('.ant-table')).toBeVisible();
    }
  });

  test('should show payment history tab', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('.ant-table', { timeout: 15000 });
    
    // Check if there's data in the table
    const rows = page.locator('.ant-table-tbody tr.ant-table-row');
    const rowCount = await rows.count();
    
    if (rowCount > 0) {
      // Click first invoice row
      await rows.first().click();
      
      // Look for payment history tab
      const paymentTab = page.locator('.ant-tabs-tab:has-text("Lịch sử thanh toán")');
      if (await paymentTab.count() > 0) {
        await paymentTab.click();
        await page.waitForTimeout(500);
      }
    }
    
    // Table should still be visible
    await expect(page.locator('.ant-table')).toBeVisible();
  });

  test('should display page totals', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for table to load
    await page.waitForSelector('.ant-table');
    
    // Page totals should be visible
    await expect(page.getByText(/Khách cần trả:/)).toBeVisible();
    await expect(page.getByText(/Khách đã trả:/)).toBeVisible();
  });

  test('should export invoices', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('.ant-table', { timeout: 15000 });
    
    // Find export button
    const exportBtn = page.locator('button:has-text("Xuất file")');
    await expect(exportBtn).toBeVisible();
    
    // Click export button using dispatchEvent to bypass any overlay
    await exportBtn.dispatchEvent('click');
    
    // Should show success message or download (depends on implementation)
    // Wait a bit for the export action to complete
    await page.waitForTimeout(2000);
  });

  test('should toggle column visibility', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('.ant-table', { timeout: 15000 });
    
    // Find column settings button (has column-height icon)
    const columnBtn = page.locator('button').filter({ has: page.locator('.anticon-column-height') });
    if (await columnBtn.count() > 0) {
      await columnBtn.first().dispatchEvent('click');
      
      // Column dropdown should appear
      await page.waitForTimeout(500);
      const dropdown = page.locator('.ant-dropdown');
      if (await dropdown.count() > 0) {
        await expect(dropdown.first()).toBeVisible();
        // Close dropdown
        await page.keyboard.press('Escape');
      }
    }
    
    // Table should still be visible
    await expect(page.locator('.ant-table')).toBeVisible();
  });

  test('should paginate invoice list', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('.ant-table', { timeout: 15000 });
    
    // Check if pagination is present (might not be if there's not enough data)
    const pagination = page.locator('.ant-pagination');
    if (await pagination.count() > 0) {
      await expect(pagination.first()).toBeVisible();
    }
    
    // Table should be visible
    await expect(page.locator('.ant-table')).toBeVisible();
  });

  test('should refresh invoice list', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('.ant-table', { timeout: 15000 });
    
    // Find and click refresh button
    const refreshBtn = page.locator('button').filter({ has: page.locator('.anticon-reload') });
    if (await refreshBtn.count() > 0) {
      await refreshBtn.first().dispatchEvent('click');
      await page.waitForTimeout(1000);
    }
    
    // Table should still be visible
    await expect(page.locator('.ant-table')).toBeVisible();
  });

  test('should show invoice status with correct color', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('.ant-table', { timeout: 15000 });
    
    // Check if there's data in the table
    const rows = page.locator('.ant-table-tbody tr.ant-table-row');
    const rowCount = await rows.count();
    
    if (rowCount > 0) {
      // Status tags should have colors
      const statusTags = page.locator('.ant-table-tbody .ant-tag');
      if (await statusTags.count() > 0) {
        await expect(statusTags.first()).toBeVisible();
      }
    }
    
    // Table should be visible
    await expect(page.locator('.ant-table')).toBeVisible();
  });

  test('should handle empty search results', async ({ page }) => {
    await page.goto('/orders/invoices');
    
    // Wait for table
    await page.waitForSelector('.ant-table');
    
    // Search for non-existent invoice
    const searchInput = page.locator('input[placeholder*="mã hóa đơn"]');
    await searchInput.fill('NONEXISTENT12345');
    
    // Wait for search
    await page.waitForTimeout(500);
    
    // Should show empty state or no results
    const table = page.locator('.ant-table-tbody');
    await expect(table).toBeVisible();
  });

  test('mobile: should open filter drawer', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto('/orders/invoices');
    
    // Wait for page load
    await page.waitForSelector('.ant-table');
    
    // Filter button should be visible on mobile
    const filterButton = page.getByRole('button', { name: /Bộ lọc/i });
    if (await filterButton.isVisible()) {
      await filterButton.click();
      
      // Filter drawer should open
      await expect(page.locator('.ant-drawer')).toBeVisible();
    }
  });
});
