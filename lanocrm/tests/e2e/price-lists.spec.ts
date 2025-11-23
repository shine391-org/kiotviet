import { test, expect } from '@playwright/test';

const mockLists = {
  success: true,
  data: [
    { id: 1, name: 'Giá VIP 2025', type: 'vip', start_date: '2025-01-01', end_date: '2025-12-31', priority: 5, status: 'active', apply_to_groups: [2] },
    { id: 2, name: 'Black Friday', type: 'custom', start_date: '2025-11-24', end_date: '2025-11-30', priority: 9, status: 'upcoming', apply_to_groups: [] },
  ],
  pagination: { page: 1, limit: 20, total: 2, total_pages: 1 },
};

test.describe('Price lists module', () => {
  test.beforeEach(async ({ page }) => {
    // Inject auth before any script runs
    await page.addInitScript(({ token, user }) => {
      localStorage.setItem('lano_token', token);
      localStorage.setItem('lano_user', JSON.stringify(user));
    }, {
      token: 'mock-token',
      user: { id: 1, username: 'admin', permissions: ['products.view', 'products.create', 'products.edit'] },
    });

    // Inline fetch stub to ensure deterministic data even if route misses
    await page.addInitScript(({ lists }) => {
      const originalFetch = window.fetch;
      window.fetch = (input, init) => {
        const url = typeof input === 'string' ? input : input.url;
        if (url.includes('/api/price-lists')) {
          return Promise.resolve(new Response(JSON.stringify(lists), {
            status: 200,
            headers: { 'Content-Type': 'application/json' },
          }));
        }
        return originalFetch(input, init);
      };
    }, { lists: mockLists });

    // Mock list API (axios uses XMLHttpRequest)
    await page.route('**/api/price-lists', async route => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(mockLists) });
      }
      // POST create
      const body = await route.request().postDataJSON();
      return route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({ success: true, data: { id: 99, ...body } }),
      });
    });

    // Mock items upsert
    await page.route('**/api/price-lists/*/items', async route => {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, inserted: 1 }) });
    });
  });

  test('shows list page with statuses and allows navigation to create', async ({ page }) => {
    await page.goto('/price-lists');
    await expect(page.locator('.ant-card-head-title', { hasText: 'Bảng giá' }).first()).toBeVisible();
    await page.screenshot({ path: 'screenshots/price-lists-list.png', fullPage: true });

    await page.getByRole('button', { name: /Tạo bảng giá/i }).click();
    await expect(page).toHaveURL(/price-lists\/create/);
  });

  test('creates price list (mocked) with one item', async ({ page }) => {
    await page.goto('/price-lists/create');

    await page.getByLabel('Tên bảng giá').fill('Test Price');
    await page.getByLabel('Độ ưu tiên').fill('3');
    await page.getByLabel('Kích hoạt').check({ force: true });
    await page.getByRole('button', { name: /Tạo mới/i }).click();

    await expect(page).toHaveURL(/price-lists$/);
  });
});
