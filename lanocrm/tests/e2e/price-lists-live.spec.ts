import { test, expect, APIRequestContext } from '@playwright/test';

const apiBase = process.env.API_BASE || 'http://localhost:8000/api';

async function createPriceList(request: APIRequestContext, name: string) {
  const res = await request.post(`${apiBase}/price-lists`, {
    data: {
      name,
      type: 'custom',
      priority: 2,
      is_active: 1,
      start_date: new Date().toISOString().slice(0, 10),
    },
  });
  if (!res.ok()) {
    const body = await res.text();
    throw new Error(`Create price list failed: ${res.status()} - ${body}`);
  }
  const body = await res.json();
  return body.data.id as number;
}

async function deletePriceList(request: APIRequestContext, id: number) {
  await request.delete(`${apiBase}/price-lists/${id}`);
}

test.describe('Price lists live API (no mocks)', () => {
  let seededId: number | null = null;

  test.beforeAll(async ({ request }) => {
    const name = `E2E Seed ${Date.now()}`;
    seededId = await createPriceList(request, name);
  });

  test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
      localStorage.setItem('lano_token', 'e2e-token');
      localStorage.setItem('lano_user', JSON.stringify({
        id: 1,
        username: 'e2e',
        permissions: [
          'products.view', 'products.create', 'products.edit',
          'price_lists.view', 'price_lists.create', 'price_lists.edit'
        ]
      }));
    });
  });

  test.afterAll(async ({ request }) => {
    if (seededId) {
      await deletePriceList(request, seededId);
    }
  });

  test('lists price lists from backend', async ({ page }) => {
    await page.goto('/price-lists');
    await expect(page.getByText('Bảng giá').first()).toBeVisible();
    await expect(page.getByRole('table')).toContainText('E2E Seed');
  });

  test('creates price list via UI hitting real API', async ({ page, request }) => {
    const uniqueName = `E2E Create ${Date.now()}`;
    await page.goto('/price-lists/create');

    await page.getByLabel('Tên bảng giá').fill(uniqueName);
    await page.getByLabel('Độ ưu tiên').fill('5');
    await page.getByLabel('Kích hoạt').check({ force: true });
    const [resp] = await Promise.all([
      page.waitForResponse(r => r.url().includes('/api/price-lists') && r.request().method() === 'POST'),
      page.getByRole('button', { name: /Tạo mới/i }).click(),
    ]);

    const debug = { req: resp.request().postDataJSON(), body: await resp.json() };
    expect(resp.status(), JSON.stringify(debug)).toBe(201);

    await expect(page).toHaveURL(/price-lists$/);
    await expect(page.getByRole('table')).toContainText(uniqueName);

    // verify via API and cleanup
    const res = await request.get(`${apiBase}/price-lists`);
    const body = await res.json();
    const created = body.data.find((row: any) => row.name === uniqueName);
    expect(created).toBeTruthy();
    if (created?.id) {
      await deletePriceList(request, created.id);
    }
  });
});
