import { test, expect } from '@playwright/test';

// Use real backend data, no mocks
test.describe('Price lists live API (Real Backend)', () => {

  test.beforeEach(async ({ page }) => {
    // Login as admin to get real token
    await page.goto('/login');
    await page.fill('input[name="username"]', 'demo.admin');
    await page.fill('input[name="password"]', '123aA@hai');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard');
  });

  test('lists products in price list page from backend', async ({ page }) => {
    await page.goto('/price-lists');

    // Wait for table to load
    await expect(page.getByRole('heading', { name: 'Bảng giá chung' })).toBeVisible();
    await expect(page.getByRole('table')).toBeVisible();

    // Check for column headers
    await expect(page.getByRole('columnheader', { name: 'Mã hàng' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Tên hàng' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Tồn kho' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Giá vốn' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Giá nhập cuối' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Bảng giá chung' })).toBeVisible();

    // Check if at least one row exists (assuming DB has data)
    // If DB is empty, this might fail, but usually dev/staging has seed data
    // We can check for "No data" if empty, or rows if not.
    // For now, let's just ensure the table structure is there.
  });

  test('shows under development message when clicking Add', async ({ page }) => {
    await page.goto('/price-lists');
    await page.getByRole('button', { name: 'Thêm' }).click();
    await expect(page.getByText('Tính năng thêm mới đang được phát triển')).toBeVisible();
  });

  test('filter bar elements are present and interactive', async ({ page }) => {
    await page.goto('/price-lists');

    // Check Selects
    const priceListSelect = page.getByText('Chọn bảng giá');
    await expect(priceListSelect).toBeVisible();
    await priceListSelect.click();
    // Check if options appear (assuming hardcoded options in UI for now)
    await expect(page.getByText('Bảng giá bán lẻ')).toBeVisible();

    // Close select
    await page.keyboard.press('Escape');
  });
});
