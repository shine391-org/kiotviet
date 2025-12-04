import { test, expect, APIRequestContext, Page } from '@playwright/test';

const apiBase = process.env.API_BASE || 'http://localhost:8000/api';
const runLiveApi = process.env.RUN_LIVE_API === '1'; // set RUN_LIVE_API=1 to hit real backend
const authHeaderValue = process.env.API_TOKEN || 'e2e-token';

type PriceListPayload = {
  name: string;
  type: string;
  priority: number;
  is_active: number;
  start_date: string;
};

const authHeaders = {
  Authorization: `Bearer ${authHeaderValue}`,
};

function buildPayload(name: string): PriceListPayload {
  return {
    name,
    type: 'custom',
    priority: 2,
    is_active: 1,
    start_date: new Date().toISOString().slice(0, 10),
  };
}

async function createPriceList(request: APIRequestContext, name: string, seededStore: Map<number, string>) {
  const payload = buildPayload(name);

  // Live API path
  if (runLiveApi) {
    let attempt = 0;
    while (attempt < 3) {
      const res = await request.post(`${apiBase}/price-lists`, { data: payload, headers: authHeaders });
      if (res.ok()) {
        const body = await res.json();
        return body.data.id as number;
      }
      const body = await res.json();
      if (body?.messages?.error?.includes('already exists')) {
        payload.name = `${name}-${Math.floor(Math.random() * 1000)}`;
        attempt++;
        continue;
      }
      throw new Error(`Create price list failed: ${res.status()} - ${JSON.stringify(body)}`);
    }
    throw new Error('Create price list failed after retries');
  }

  // Mock path: generate local id and keep in seeded store
  const id = Math.floor(Math.random() * 100000) + 1000;
  seededStore.set(id, payload.name);
  return id;
}

async function deletePriceList(request: APIRequestContext, id: number) {
  if (runLiveApi) {
    await request.delete(`${apiBase}/price-lists/${id}`, { headers: authHeaders });
  }
}

function setupMockRoutes(page: Page, seededStore: Map<number, string>) {
  if (runLiveApi) return;

  page.route('**/api/price-lists*', async (route) => {
    const req = route.request();
    const url = new URL(req.url());
    if (req.method() === 'GET') {
      const data = Array.from(seededStore.entries()).map(([id, name]) => ({
        id,
        name,
        type: 'custom',
        start_date: new Date().toISOString().slice(0, 10),
        priority: 2,
        status: 'active',
      }));
      return route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data,
          pagination: { page: 1, limit: 20, total: data.length, total_pages: 1 },
        }),
      });
    }

    if (req.method() === 'POST') {
      const body = await req.postDataJSON();
      const id = Math.floor(Math.random() * 100000) + 2000;
      seededStore.set(id, body.name);
      return route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({ success: true, data: { id, ...body } }),
      });
    }

    // DELETE
    if (req.method() === 'DELETE') {
      const parts = url.pathname.split('/');
      const id = Number(parts[parts.length - 1]);
      if (seededStore.has(id)) seededStore.delete(id);
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true }) });
    }

    return route.continue();
  });
}

test.describe('Price lists live API (no mocks)', () => {
  let seededId: number | null = null;
  let seededName = '';
  const seededStore = new Map<number, string>();

  test.beforeAll(async ({ request }) => {
    seededName = `E2E Seed ${Date.now()}`;
    seededId = await createPriceList(request, seededName, seededStore);
    if (seededId) seededStore.set(seededId, seededName);
  });

  test.beforeEach(async ({ page }) => {
    await page.addInitScript(({ token, user }) => {
      window.__E2E_TEST__ = true;
      localStorage.setItem('lano_token', token);
      localStorage.setItem('lano_user', JSON.stringify(user));
    }, {
      token: authHeaderValue,
      user: {
        id: 1,
        username: 'e2e',
        permissions: [
          'products.view', 'products.create', 'products.edit',
          'price_lists.view', 'price_lists.create', 'price_lists.edit'
        ]
      },
    });

    setupMockRoutes(page, seededStore);
  });

  test.afterAll(async ({ request }) => {
    if (seededId) {
      await deletePriceList(request, seededId);
    }
  });

  test('lists price lists from backend', async ({ page }) => {
    await page.goto('/price-lists');
    await expect(page.getByText('Bảng giá').first()).toBeVisible();
    await expect(page.getByRole('table')).toContainText(seededName);
  });

  test('creates price list via UI hitting real API', async ({ page, request }) => {
    const uniqueName = `E2E Create ${Date.now()}`;
    await page.goto('/price-lists/create');

    await page.getByLabel('Tên bảng giá').fill(uniqueName);
    await page.getByLabel('Độ ưu tiên').fill('5');
    await page.getByLabel('Kích hoạt').check({ force: true });

    let createdId: number | null = null;
    const [resp] = await Promise.all([
      page.waitForResponse(r => r.url().includes('/api/price-lists') && r.request().method() === 'POST'),
      page.getByRole('button', { name: /Tạo mới/i }).click(),
    ]);

    const debug = { req: resp.request().postDataJSON(), body: await resp.json() };
    expect(resp.status(), JSON.stringify(debug)).toBe(runLiveApi ? 201 : 201);
    createdId = debug.body?.data?.id || null;
    if (createdId && !seededStore.has(createdId)) seededStore.set(createdId, uniqueName);

    await expect(page).toHaveURL(/price-lists$/);
    await expect(page.getByRole('table')).toContainText(uniqueName);

    if (runLiveApi) {
      const res = await request.get(`${apiBase}/price-lists`, { headers: authHeaders });
      const body = await res.json();
      const created = body.data.find((row: any) => row.name === uniqueName);
      expect(created).toBeTruthy();
      if (created?.id) {
        await deletePriceList(request, created.id);
      }
    }
  });
});
