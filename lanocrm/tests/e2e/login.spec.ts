import { test, expect } from '@playwright/test';

test.describe('Login Page', () => {
  test('has title', async ({ page }) => {
    await page.goto('/login');
    await expect(page).toHaveTitle(/Lano CRM/);
  });

  test('login form is visible', async ({ page }) => {
    await page.goto('/login');

    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('should show error on invalid credentials (mocked)', async ({ page }) => {
    // Mock API failure
    await page.route('**/api/auth/login', async route => {
      await route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Tên đăng nhập hoặc mật khẩu không đúng' })
      });
    });

    await page.goto('/login');
    await page.locator('input[name="username"]').fill('wronguser');
    await page.locator('input[name="password"]').fill('wrongpass');
    await page.locator('button[type="submit"]').click();

    await expect(page.locator('.error-message')).toBeVisible();
    await expect(page.locator('.error-message')).toContainText('Tên đăng nhập hoặc mật khẩu không đúng');
  });

  test('should login successfully with mock api', async ({ page }) => {
    // Mock API success
    await page.route('**/api/auth/login', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          token: 'mock-token-123456',
          user: {
            id: 1,
            username: 'admin',
            role: 'super-admin',
            permissions: ['users.view', 'products.view']
          }
        })
      });
    });

    await page.goto('/login');
    await page.locator('input[name="username"]').fill('admin');
    await page.locator('input[name="password"]').fill('password123');
    await page.locator('button[type="submit"]').click();

    // Expect redirect to dashboard
    await expect(page).toHaveURL(/\/dashboard/);
  });
});
