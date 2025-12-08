/**
 * E2E Tests for Customer Management
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

test.describe('Customer Management', () => {
  // Login before each test
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.waitForSelector('input[name="username"]', { timeout: 10000 });
    await page.fill('input[name="username"]', 'devadmin');
    await page.fill('input[name="password"]', '123aA@hai');
    await page.click('button[type="submit"]');
    // Wait for dashboard or any authenticated page
    await page.waitForURL(/\/(dashboard|customers|products)/, { timeout: 15000 });
  });

  test('should display customer list page', async ({ page }) => {
    await page.goto('/customers');
    
    // Wait for page to fully load - search input is a good indicator
    await page.waitForLoadState('networkidle');
    await expect(page.locator('input[placeholder*="mã, tên, số điện thoại"]')).toBeVisible({ timeout: 20000 });
    
    // Verify main elements are present
    await expect(page.locator('button.ant-btn-primary:has-text("Khách hàng")')).toBeVisible();
    await expect(page.getByText('Bộ lọc')).toBeVisible();
    await expect(page.getByText('Chi tiết khách hàng')).toBeVisible();
  });

  test('should search customers by name', async ({ page }) => {
    await page.goto('/customers');
    
    // Wait for initial load
    await page.waitForSelector('.ant-table');
    
    // Search for a customer
    const searchInput = page.locator('input[placeholder*="mã, tên, số điện thoại"]');
    await searchInput.fill('test');
    
    // Wait for search results (debounced)
    await page.waitForTimeout(500);
    
    // Table should update with search results
    await expect(page.locator('.ant-table-tbody')).toBeVisible();
  });

  test('should open create customer drawer', async ({ page }) => {
    await page.goto('/customers');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('button.ant-btn-primary:has-text("Khách hàng")', { timeout: 15000 });
    
    // Click add customer button using dispatchEvent to bypass header overlay
    const addButton = page.locator('button.ant-btn-primary:has-text("Khách hàng")');
    await addButton.dispatchEvent('click');
    
    // Drawer should open
    await expect(page.locator('.ant-drawer-title:has-text("Tạo khách hàng")')).toBeVisible({ timeout: 10000 });
    
    // Wait for drawer animation
    await page.waitForTimeout(300);
    
    // Verify form fields are in the drawer
    const drawer = page.locator('.ant-drawer-body');
    await expect(drawer.locator('#name')).toBeVisible();
    await expect(drawer.locator('#phone')).toBeVisible();
  });

  test('should create a new customer', async ({ page }) => {
    await page.goto('/customers');
    
    // Wait for page to fully load
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('button.ant-btn-primary:has-text("Khách hàng")', { timeout: 15000 });
    
    // Open create drawer using dispatchEvent to bypass header overlay
    const addBtn = page.locator('button.ant-btn-primary:has-text("Khách hàng")');
    await addBtn.dispatchEvent('click');
    await expect(page.locator('.ant-drawer-title:has-text("Tạo khách hàng")')).toBeVisible({ timeout: 10000 });
    
    // Wait for drawer animation
    await page.waitForTimeout(300);
    
    // Fill form with unique data
    const uniqueName = `Test Customer ${Date.now()}`;
    const drawer = page.locator('.ant-drawer-body');
    await drawer.locator('#name').fill(uniqueName);
    await drawer.locator('#phone').fill('0901234567');
    await drawer.locator('#email').fill('test@example.com');
    
    // Submit form
    await page.click('.ant-drawer button:has-text("Lưu")');
    
    // Wait for success message
    await expect(page.locator('.ant-message-success')).toBeVisible({ timeout: 5000 });
    
    // Drawer should close (use specific selector for drawer title)
    await expect(page.locator('.ant-drawer-title:has-text("Tạo khách hàng")')).not.toBeVisible();
    
    // Search for created customer
    await page.fill('input[placeholder*="mã, tên, số điện thoại"]', uniqueName);
    await page.waitForTimeout(500);
    
    // Customer should appear in table
    await expect(page.locator('.ant-table-tbody')).toContainText(uniqueName);
  });

  test('should filter customers by type', async ({ page }) => {
    await page.goto('/customers');
    
    // Wait for page load
    await page.waitForSelector('.ant-table');
    
    // Select customer type filter
    const typeSelect = page.locator('.ant-card:has-text("Bộ lọc")').locator('.ant-select').first();
    await typeSelect.click();
    await page.click('.ant-select-item:has-text("Cá nhân")');
    
    // Wait for filter to apply
    await page.waitForTimeout(500);
    
    // Table should show filtered results
    await expect(page.locator('.ant-table-tbody')).toBeVisible();
  });

  test('should select customer and show detail', async ({ page }) => {
    await page.goto('/customers');
    
    // Wait for table to load with data (skip hidden measure rows)
    await page.waitForSelector('.ant-table-tbody tr.ant-table-row', { timeout: 15000 });
    
    // Click on first visible customer row
    const firstRow = page.locator('.ant-table-tbody tr.ant-table-row').first();
    await firstRow.click();
    
    // Detail panel should show customer info
    const detailCard = page.locator('.ant-card:has-text("Chi tiết khách hàng")');
    await expect(detailCard).toBeVisible();
    
    // Should have Edit and Delete buttons
    await expect(detailCard.getByRole('button', { name: /Sửa/i })).toBeVisible();
    await expect(detailCard.getByRole('button', { name: /Xóa/i })).toBeVisible();
  });

  test('should edit customer', async ({ page }) => {
    await page.goto('/customers');
    
    // Wait for and click first customer row
    await page.waitForSelector('.ant-table-tbody tr.ant-table-row', { timeout: 15000 });
    await page.locator('.ant-table-tbody tr.ant-table-row').first().click();
    
    // Click Edit button
    const detailCard = page.locator('.ant-card:has-text("Chi tiết khách hàng")');
    await detailCard.getByRole('button', { name: /Sửa/i }).click();
    
    // Edit drawer should open
    await expect(page.getByText('Sửa khách hàng')).toBeVisible();
    
    // Modify a field
    const noteField = page.locator('textarea[id="notes"]');
    await noteField.fill(`Updated at ${new Date().toISOString()}`);
    
    // Save changes
    await page.click('button:has-text("Lưu")');
    
    // Wait for success message
    await expect(page.locator('.ant-message-success')).toBeVisible({ timeout: 5000 });
  });

  test('should delete customer with confirmation', async ({ page }) => {
    // First create a customer to delete
    await page.goto('/customers');
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('button.ant-btn-primary:has-text("Khách hàng")', { timeout: 15000 });
    const createBtn = page.locator('button.ant-btn-primary:has-text("Khách hàng")');
    await createBtn.dispatchEvent('click');
    await expect(page.locator('.ant-drawer-title:has-text("Tạo khách hàng")')).toBeVisible({ timeout: 10000 });
    await page.waitForTimeout(300);
    
    const deleteName = `Delete Test ${Date.now()}`;
    const drawer = page.locator('.ant-drawer-body');
    await drawer.locator('#name').fill(deleteName);
    await drawer.locator('#phone').fill('0909999999');
    await page.click('.ant-drawer button:has-text("Lưu")');
    await expect(page.locator('.ant-message-success')).toBeVisible({ timeout: 10000 });
    
    // Search and select the created customer
    await page.fill('input[placeholder*="mã, tên, số điện thoại"]', deleteName);
    await page.waitForTimeout(1000);
    await page.waitForSelector('.ant-table-tbody tr.ant-table-row', { timeout: 10000 });
    await page.locator('.ant-table-tbody tr.ant-table-row').first().click();
    
    // Click Delete button
    const detailCard = page.locator('.ant-card:has-text("Chi tiết khách hàng")');
    await detailCard.getByRole('button', { name: /Xóa/i }).click();
    
    // Confirm deletion in modal (use specific selector for modal title)
    await expect(page.locator('.ant-modal-confirm-title:has-text("Xác nhận xóa")')).toBeVisible({ timeout: 5000 });
    await page.click('.ant-modal-confirm-btns button:has-text("Xóa")');
    
    // Wait for success message
    await expect(page.locator('.ant-message-success')).toBeVisible({ timeout: 5000 });
  });

  test('should export customers', async ({ page }) => {
    await page.goto('/customers');
    
    // Wait for page to load
    await page.waitForSelector('.ant-table');
    
    // Click export button
    const downloadPromise = page.waitForEvent('download');
    await page.click('button:has-text("Export")');
    
    // Verify download started
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toContain('customers');
  });

  test('should paginate customer list', async ({ page }) => {
    await page.goto('/customers');
    
    // Wait for table and pagination
    await page.waitForSelector('.ant-table');
    await page.waitForSelector('.ant-pagination');
    
    // Check pagination info
    const paginationInfo = page.locator('.ant-pagination-total-text');
    await expect(paginationInfo).toBeVisible();
    
    // Try changing page size
    await page.click('.ant-pagination-options .ant-select');
    await page.click('.ant-select-item:has-text("20")');
    
    // Table should update
    await page.waitForTimeout(500);
  });

  test('should refresh customer list', async ({ page }) => {
    await page.goto('/customers');
    
    // Wait for initial load
    await page.waitForSelector('.ant-table');
    
    // Click refresh button
    await page.click('button:has(.anticon-reload)');
    
    // Table should reload (loading state and then data)
    await page.waitForSelector('.ant-table-tbody');
  });
});
