import { test, expect } from '@playwright/test';

const USERNAME = process.env.E2E_ADMIN_USER || 'admin';
const PASSWORD = process.env.E2E_ADMIN_PASS || '123aA@hai';

// E2E: login thật bằng admin, mở menu Hàng hoá -> Bảng giá, tới trang price-lists
// Yêu cầu: backend + FE dev server đang chạy, tài khoản admin hợp lệ.
test('admin navigates to price lists from top menu', async ({ page }) => {
  await page.goto('/login');

  await page.locator('input[name="username"]').fill(USERNAME);
  await page.locator('input[name="password"]').fill(PASSWORD);

  const [resp] = await Promise.all([
    page.waitForResponse((r) => r.url().includes('/api/auth/login') && r.request().method() === 'POST'),
    page.locator('button[type="submit"]').click(),
  ]);

  // Allow 200 or 201 for successful login
  expect([200, 201]).toContain(resp.status());
  await page.waitForURL(/dashboard|\/$/, { timeout: 10000 });

  // Mở dropdown Hàng hoá và click Bảng giá
  await page.getByRole('button', { name: /Hàng hoá|Hàng hóa/i }).click();
  await page.getByRole('link', { name: /Bảng giá/i }).click();

  await page.waitForURL(/price-lists/, { timeout: 10000 });
  await expect(page).toHaveURL(/price-lists/);
  await expect(page.getByText(/Bảng giá/i)).toBeVisible();
});
