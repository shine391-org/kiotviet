/**
 * Product flows E2E - live API (no mocks)
 * Scenarios: create product with category, upload/change images, update price,
 * edit variant price & images against real backend/database.
 */
import { test, expect, APIRequestContext } from '@playwright/test';
import path from 'path';
import fs from 'fs';

const apiBase = process.env.API_BASE || 'http://localhost:8000/api';
const e2eUser = process.env.E2E_USER || 'demo.admin';
const e2ePass = process.env.E2E_PASS || '123aA@hai';
const uploadsPattern = /\/uploads\//;
let consoleErrors: string[] = [];

async function expectImageReachable(page, locator) {
  await locator.waitFor({ state: 'attached', timeout: 40000 });
  const src = await locator.getAttribute('src');
  expect(src).toBeTruthy();
  expect(src!).toMatch(uploadsPattern);
  const resp = await page.context().request.get(src!);
  expect(resp.ok()).toBeTruthy();
}

type AuthState = { token: string; user: any };
type Category = { id: number; name: string };

async function login(request: APIRequestContext): Promise<AuthState> {
  const res = await request.post(`${apiBase}/auth/login`, {
    data: { username: e2eUser, password: e2ePass },
  });
  expect(res.ok()).toBeTruthy();
  const body = await res.json();
  if (!body.token || !body.user) {
    throw new Error('Login response missing token/user');
  }
  return { token: body.token, user: body.user };
}

async function pickCategory(request: APIRequestContext, token: string): Promise<Category> {
  const res = await request.get(`${apiBase}/product-categories`, {
    headers: { Authorization: `Bearer ${token}` },
    params: { limit: 1 },
  });
  expect(res.ok()).toBeTruthy();
  const body = await res.json();
  const cat = body.data?.[0];
  if (!cat?.id) {
    throw new Error('No category available for E2E');
  }
  return { id: cat.id, name: cat.name };
}

async function createProductViaApi(
  request: APIRequestContext,
  token: string,
  categoryId: number,
  namePrefix = 'E2E Product'
) {
  const code = `E2E-${Date.now()}`;
  const payload = {
    code,
    name: `${namePrefix} ${code}`,
    category_id: [categoryId],
    product_type: 'goods',
    unit: 'cái',
    selling_price: 150000,
    purchase_price: 90000,
    wholesale_price: 0,
    stock_quantity: 5,
    is_active: 1,
    is_featured: 0,
    is_available_online: 1,
  };
  const res = await request.post(`${apiBase}/products`, {
    headers: { Authorization: `Bearer ${token}` },
    data: payload,
  });
  expect(res.ok()).toBeTruthy();
  const body = await res.json();
  const id = body.data?.id;
  if (!id) {
    throw new Error('Create product response missing id');
  }
  return { id, code, name: payload.name };
}

async function createVariantViaApi(
  request: APIRequestContext,
  token: string,
  productId: number
) {
  const sku = `SKU-${productId}-${Date.now()}`;
  const res = await request.post(`${apiBase}/products/${productId}/variants`, {
    headers: { Authorization: `Bearer ${token}` },
    multipart: {
      sku,
      variant_name: `Variant ${sku}`,
      price: '120000',
      cost_price: '80000',
      stock_quantity: '3',
    },
  });
  expect(res.ok()).toBeTruthy();
  const body = await res.json();
  const id = body.data?.id || body.data?.variant?.id || body.id;
  if (!id) {
    throw new Error('Create variant response missing id');
  }
  return { id, sku };
}

async function attachProductImageToVariant(
  request: APIRequestContext,
  token: string,
  productId: number,
  variantId: number,
  fallbackFilePath: string
) {
  // 1) Upload ảnh trực tiếp cho variant
  if (fs.existsSync(fallbackFilePath)) {
    const variantUpload = await request.post(`${apiBase}/variants/${variantId}/upload-multiple`, {
      headers: { Authorization: `Bearer ${token}` },
      multipart: {
        variant_id: `${variantId}`,
        'files[]': {
          name: path.basename(fallbackFilePath),
          mimeType: 'image/png',
          buffer: fs.readFileSync(fallbackFilePath),
        },
      },
    });
    if (variantUpload.ok()) {
      // Seed ảnh cho product để FE fallback khi variant chưa trả images
      await request.post(`${apiBase}/products/upload-multiple`, {
        headers: { Authorization: `Bearer ${token}` },
        multipart: {
          product_id: `${productId}`,
          'files[]': {
            name: path.basename(fallbackFilePath),
            mimeType: 'image/png',
            buffer: fs.readFileSync(fallbackFilePath),
          },
        },
      });
      return true;
    }
  }

  // 2) Fallback: lấy ảnh từ product và attach
  const imgRes = await request.get(`${apiBase}/products/${productId}/images`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  let imgs = [];
  if (imgRes.ok()) {
    imgs = (await imgRes.json())?.data || [];
  }

  // Nếu chưa có ảnh, upload một ảnh mẫu cho product
  if (!imgs.length && fs.existsSync(fallbackFilePath)) {
    const uploadRes = await request.post(`${apiBase}/products/upload-multiple`, {
      headers: { Authorization: `Bearer ${token}` },
      multipart: {
        product_id: `${productId}`,
        'files[]': {
          name: path.basename(fallbackFilePath),
          mimeType: 'image/png',
          buffer: fs.readFileSync(fallbackFilePath),
        },
      },
    });
    if (uploadRes.ok()) {
      const refreshed = await request.get(`${apiBase}/products/${productId}/images`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      if (refreshed.ok()) {
        imgs = (await refreshed.json())?.data || [];
      }
    }
  }

  if (!imgs.length) return false;

  const firstId = imgs[0].id || imgs[0].image_id || imgs[0].media_id;
  if (!firstId) return false;

  const attachRes = await request.post(`${apiBase}/variants/${variantId}/images/attach-multiple`, {
    headers: { Authorization: `Bearer ${token}` },
    data: { image_ids: [firstId] },
  });
  return attachRes.ok();
}

test.describe('Products & Variants - live API', () => {
  let auth: AuthState;
  let category: Category;
  let seededVariant: { id: number; sku: string };
  let seededVariantProductId: number;
  const logoPath = path.join(process.cwd(), 'public', 'logo.png');
  const altImagePath = path.join(process.cwd(), 'suppliers-centered.png');

  test.beforeAll(async ({ request }) => {
    auth = await login(request);
    category = await pickCategory(request, auth.token);
    const productForVariant = await createProductViaApi(
      request,
      auth.token,
      category.id,
      'E2E Variant Product'
    );
    seededVariantProductId = productForVariant.id;
    seededVariant = await createVariantViaApi(request, auth.token, productForVariant.id);
  });

  test.beforeEach(async ({ page }) => {
    consoleErrors = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });
    await page.addInitScript(({ token, user }) => {
      window.__E2E_TEST__ = true;
      localStorage.setItem('lano_token', token);
      localStorage.setItem('lano_user', JSON.stringify(user));
    }, { token: auth.token, user: auth.user });
  });

  test.afterEach(async () => {
    const filtered = consoleErrors.filter((msg) => {
      const lower = msg.toLowerCase();
      if (msg.startsWith('Warning:')) return false;
      if (lower.includes('failed to load resource') && lower.includes('404')) return false;
      if (lower.includes('attribute-values') || lower.includes('used-attribute-options')) return false;
      return true;
    });
    expect(filtered, `Console errors detected: ${filtered.join(' | ')}`).toEqual([]);
  });

  test('creates product, selects category, uploads & switches images, updates price (real API)', async ({ page }) => {
    test.setTimeout(90000);
    const code = `E2E-${Date.now()}`;
    const name = `E2E Product ${code}`;

    await page.goto('/products/create');
    await page.getByPlaceholder('e.g., SKU-001').fill(code);
    await page.getByPlaceholder('Product name').fill(name);

    // Select category in TreeSelect (use data-testid to avoid overlay issues)
    const categorySelect = page.getByTestId('category-tree-select');
    await categorySelect.scrollIntoViewIfNeeded();
    await categorySelect.click({ force: true });
    await page.getByRole('treeitem', { name: new RegExp(category.name, 'i') }).first().click();

    await page.locator('input[name="selling_price"]').fill('150000');
    await page.locator('input[name="purchase_price"]').fill('90000');
    await page.locator('input[name="stock_quantity"]').fill('5');

    const [createResp] = await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/api/products') && resp.request().method() === 'POST'),
      page.getByRole('button', { name: 'Thêm sản phẩm' }).click(),
    ]);
    expect(createResp.ok()).toBeTruthy();
    const createBody = await createResp.json();
    const newProductId = createBody.data?.id;
    if (!createBody.success || !newProductId) {
      throw new Error(`Create product failed: ${JSON.stringify(createBody)}`);
    }

    await expect(page).toHaveURL(/\/products$/, { timeout: 20000 });
    await expect(page.getByText(code, { exact: true })).toBeVisible();

    // Go to edit page
    await page.goto(`/products/edit/${newProductId}`);
    await expect(page.getByText('Chỉnh sửa sản phẩm')).toBeVisible();

    // Upload first image
    const uploadButton = page.getByRole('button', { name: /Chọn ảnh để upload/i }).first();
    await uploadButton.scrollIntoViewIfNeeded();
    const [chooser1] = await Promise.all([
      page.waitForEvent('filechooser'),
      uploadButton.click(),
    ]);
    await chooser1.setFiles(logoPath);
    const imgGrid = page.locator('[class*="imageItem"]');
    const initialCount = await imgGrid.count();
    const [uploadResp1] = await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/api/products/upload-multiple') && resp.request().method() === 'POST'),
      page.getByRole('button', { name: /Upload 1 ảnh/i }).click(),
    ]);
    expect(uploadResp1.ok()).toBeTruthy();
    await expect(imgGrid).toHaveCount(initialCount + 1, { timeout: 10000 });

    // Upload second image and set it as primary
    const [chooser2] = await Promise.all([
      page.waitForEvent('filechooser'),
      uploadButton.click(),
    ]);
    await chooser2.setFiles(altImagePath);
    const [uploadResp2] = await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/api/products/upload-multiple') && resp.request().method() === 'POST'),
      page.getByRole('button', { name: /Upload 1 ảnh/i }).click(),
    ]);
    expect(uploadResp2.ok()).toBeTruthy();
    await expect(imgGrid).toHaveCount(initialCount + 2, { timeout: 15000 });

    // Ensure images point to API host (avoid /uploads on FE origin)
    const firstImage = imgGrid.first().locator('img');
    await expectImageReachable(page, firstImage);
    const lastImage = imgGrid.last().locator('img');
    await expectImageReachable(page, lastImage);
    const primarySrc = await lastImage.getAttribute('src');
    const primaryFile = primarySrc?.split('/').pop();

    const imageCards = page.locator('[class*="imageItem"]');
    const lastCard = imageCards.last();
    await lastCard.getByRole('button').first().click({ force: true }); // set primary (UI updates via state)

    // Update selling price
    await page.locator('input[name="selling_price"]').fill('175000');
    const updateBtn = page.getByRole('button', { name: 'Cập nhật sản phẩm' });
    await expect(updateBtn).toBeEnabled({ timeout: 15000 });
    const [updateResp] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes(`/api/products/${newProductId}`) && resp.request().method().toUpperCase() === 'PUT'
      ),
      updateBtn.click({ force: true }),
    ]);
    expect(updateResp.ok()).toBeTruthy();
    await expect(page).toHaveURL(/\/products$/, { timeout: 20000 });
    await expect(page.getByText(name)).toBeVisible({ timeout: 10000 });

    // Verify thumbnail in list uses primary image
    const row = page.locator('tr', { hasText: code }).first();
    const thumb = row.locator('img').first();
    await expect.poll(async () => await thumb.getAttribute('src'), { timeout: 20000 }).not.toContain('placeholder');
    if (primaryFile) {
      await expect(thumb).toHaveAttribute('src', new RegExp(primaryFile));
    }
  });

  test('edits variant price and ensures images are reachable (real API)', async ({ page, request }) => {
    test.setTimeout(60000);
    const attached = await attachProductImageToVariant(request, auth.token, seededVariantProductId, seededVariant.id, logoPath);
    expect(attached).toBeTruthy();
    await page.goto(`/products/variants/edit/${seededVariant.id}`);
    await expect(page.getByText('Chỉnh sửa biến thể')).toBeVisible();

    // Upload variant image via UI to ensure gallery renders
    const uploadButton = page.getByRole('button', { name: /Chọn ảnh để upload/i }).first();
    const [chooser] = await Promise.all([
      page.waitForEvent('filechooser'),
      uploadButton.click(),
    ]);
    await chooser.setFiles(altImagePath);
    const variantGrid = page.locator('[class*="imageItem"]');
    const initialVariantCount = await variantGrid.count();
    const [uploadResp] = await Promise.all([
      page.waitForResponse((resp) => resp.url().includes(`/api/variants/${seededVariant.id}/upload-multiple`) && resp.request().method() === 'POST'),
      page.getByRole('button', { name: /Upload 1 ảnh/i }).click(),
    ]);
    expect(uploadResp.ok()).toBeTruthy();

    await expect.poll(async () => await variantGrid.count(), { timeout: 20000 }).toBeGreaterThan(0);
    const variantFirstImage = variantGrid.first().locator('img');
    await expectImageReachable(page, variantFirstImage);

    // Attribute manager: verify empty state and disabled actions before data exists
    const attrSection = page.getByText('Thuộc tính biến thể').locator('..');
    await expect(attrSection.getByText('Chưa có thuộc tính nào')).toBeVisible();
    const addAttrBtn = page.getByRole('button', { name: /Thêm/i }).first();
    await expect(addAttrBtn).toBeDisabled();
    const createVariantsBtn = page.getByRole('button', { name: /Tạo biến thể/i }).first();
    await expect(createVariantsBtn).toBeDisabled();

    // Update price
    const priceInput = page.getByText('Giá bán *').locator('..').locator('input');
    await priceInput.fill('190000');
    const updateVariantBtn = page.getByRole('button', { name: 'Cập nhật biến thể' });
    await expect(updateVariantBtn).toBeEnabled({ timeout: 15000 });
    const [updateResp] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes(`/api/variants/${seededVariant.id}`) && resp.request().method().toUpperCase() === 'PUT'
      ),
      updateVariantBtn.click({ force: true }),
    ]);
    expect(updateResp.ok()).toBeTruthy();
    await expect(page).toHaveURL(/\/products$/, { timeout: 20000 });
    await expect(page.getByRole('heading', { name: 'Danh sách hàng hóa' })).toBeVisible({ timeout: 10000 });
  });

  test('invalid products route shows 404 page', async ({ page }) => {
    await page.goto('/products/ediit');
    await expect(page.getByText('404')).toBeVisible();
  });
});
