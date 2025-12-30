import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

test.describe('Shortcuts Modal', () => {
  test.beforeEach(async ({ page }) => {
    // 1. Mock API responses to avoid backend dependency
    await page.route('**/api/pos/products*', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ success: true, data: [] }),
      });
    });

    await page.route('**/api/pos/sellers', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ success: true, data: [] }),
      });
    });

    await page.route('**/api/price-lists*', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ success: true, data: [] }),
        });
      });

    // Mock Login by setting localStorage directly
    const mockUser = {
        id: 1,
        username: 'admin',
        full_name: 'Administrator',
        email: 'admin@example.com',
        role: 'admin',
        permissions: ['pos.access', 'product.view']
    };

    await page.goto('/login'); // Go to login to ensure context is available
    await page.evaluate(({ user, token }) => {
        localStorage.setItem('lano_token', token);
        localStorage.setItem('lano_user', JSON.stringify(user));
    }, { user: mockUser, token: 'mock-token-123' });

    // Also mock /users/me check which might happen on reload
    await page.route('**/api/users/me', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ user: mockUser }),
        });
      });
  });

  test('should open and display shortcuts help modal', async ({ page }) => {
    // Navigate to POS page
    await page.goto('/pos');

    // Wait for the header to be visible, specifically the user menu button
    const userMenuButton = page.locator('header .ant-btn .anticon-menu').locator('..');
    await expect(userMenuButton).toBeVisible();

    // 2. Open the User Menu
    await userMenuButton.click();

    // 3. Click "Phím tắt" menu item
    // Ant Design Dropdown renders in a portal, often directly under body.
    const menuItem = page.getByText('Phím tắt');
    await expect(menuItem).toBeVisible();
    await menuItem.click();

    // 4. Verify Modal appears
    // Ant Design Modal also renders in a portal
    const modalTitle = page.getByRole('dialog').getByText('Danh sách phím tắt');
    await expect(modalTitle).toBeVisible();

    // 5. Verify Content
    const modalContent = page.getByRole('dialog');
    await expect(modalContent.getByText('Bán nhanh')).toBeVisible();
    await expect(modalContent.getByText('F1')).toBeVisible();
    await expect(modalContent.getByText('Bán thường')).toBeVisible();
    await expect(modalContent.getByText('F2')).toBeVisible();
    await expect(modalContent.getByText('Tìm hàng hóa')).toBeVisible();
    await expect(modalContent.getByText('F3')).toBeVisible();

    // Screenshot
    await page.screenshot({ path: 'tests/e2e/screenshots/shortcuts_modal.png', fullPage: true });

    // 6. Close Modal
    await page.locator('.ant-modal-close').click();
    await expect(modalTitle).not.toBeVisible();
  });
});
