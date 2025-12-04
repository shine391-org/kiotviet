import { test, expect, APIRequestContext } from '@playwright/test';
import path from 'path';
import fs from 'fs';

const apiBase = process.env.API_BASE || 'http://localhost:8000/api';
const e2eUser = process.env.E2E_USER || 'demo.admin';
const e2ePass = process.env.E2E_PASS || '123aA@hai';

interface AuthState {
  token: string;
}

interface Category {
  id: number;
  name: string;
}

async function login(request: APIRequestContext): Promise<AuthState> {
  const res = await request.post(`${apiBase}/auth/login`, {
    data: { username: e2eUser, password: e2ePass },
  });
  expect(res.ok()).toBeTruthy();
  const body = await res.json();
  return { token: body.token };
}

async function pickCategory(request: APIRequestContext, token: string): Promise<Category> {
  const res = await request.get(`${apiBase}/product-categories`, {
    headers: { Authorization: `Bearer ${token}` },
    params: { limit: 1 },
  });
  if (!res.ok()) {
    console.log('pickCategory failed:', await res.text());
  }
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
  categoryId: number
) {
  const code = `P-${Date.now()}`;
  const payload = {
    code,
    name: `Product ${code}`,
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
  return { id: body.data?.id, code, name: payload.name };
}

async function createVariantViaApi(
  request: APIRequestContext,
  token: string,
  productId: number
) {
  const sku = `SKU-${productId}-${Date.now()}`;
  // Use multipart form-data to test backend fix
  const res = await request.post(`${apiBase}/products/${productId}/variants`, {
    headers: { Authorization: `Bearer ${token}` },
    multipart: {
      sku,
      variant_name: `Variant ${sku}`,
      price: '120000',
      cost_price: '80000',
    },
  });
  expect(res.ok()).toBeTruthy();
  const body = await res.json();
  return { id: body.data.id, sku };
}

async function attachProductImageToVariant(
  request: APIRequestContext,
  token: string,
  productId: number,
  variantId: number,
  imagePath: string
) {
  // 1. Upload to variant directly
  if (fs.existsSync(imagePath)) {
    const variantUpload = await request.post(`${apiBase}/variants/${variantId}/upload-multiple`, {
      headers: { Authorization: `Bearer ${token}` },
      multipart: {
        variant_id: `${variantId}`,
        'files[]': {
          name: path.basename(imagePath),
          mimeType: 'image/png',
          buffer: fs.readFileSync(imagePath),
        },
      },
    });
    if (variantUpload.ok()) {
      return true;
    } else {
      console.log('Direct variant upload failed:', await variantUpload.text());
    }
  }
  return false;
}

test.describe('Reproduction: Variant Image Update', () => {
  let auth: AuthState;
  let category: Category;
  let product: { id: number; code: string; name: string };
  let variant: { id: number; sku: string };

  const logoPath = path.join(process.cwd(), 'public', 'logo.png');
  const altImagePath = path.join(process.cwd(), 'suppliers-centered.png');

  test.beforeAll(async ({ request }) => {
    auth = await login(request);
    category = await pickCategory(request, auth.token);
    product = await createProductViaApi(request, auth.token, category.id);
    variant = await createVariantViaApi(request, auth.token, product.id);
  });

  test('should update variant image correctly', async ({ page, request }) => {
    test.setTimeout(60000);

    // 1. Attach initial image (logo.png) to variant
    const attached = await attachProductImageToVariant(request, auth.token, product.id, variant.id, logoPath);
    expect(attached).toBeTruthy();

    // 2. Go to edit variant page
    await page.goto(`/login`);
    await page.getByPlaceholder('Tên đăng nhập').fill(e2eUser);
    await page.getByPlaceholder('Mật khẩu').fill(e2ePass);
    await page.getByRole('button', { name: 'Đăng nhập' }).click();
    await page.waitForURL('**/dashboard');

    page.on('console', msg => console.log('BROWSER LOG:', msg.text()));

    await page.goto(`/products/variants/edit/${variant.id}`);
    await expect(page.getByText('Chỉnh sửa biến thể')).toBeVisible();

    // Verify initial image is present
    const variantGrid = page.locator('[class*="imageItem"]');
    await expect(variantGrid).toHaveCount(1);

    // 3. Upload new image (altImagePath)
    const uploadButton = page.getByRole('button', { name: /Chọn ảnh để upload/i }).first();
    const [chooser] = await Promise.all([
      page.waitForEvent('filechooser'),
      uploadButton.click(),
    ]);
    await chooser.setFiles(altImagePath);

    const [uploadResp] = await Promise.all([
      page.waitForResponse((resp) => resp.url().includes(`/api/variants/${variant.id}/upload-multiple`) && resp.request().method() === 'POST'),
      page.getByRole('button', { name: /Upload 1 ảnh/i }).click(),
    ]);
    expect(uploadResp.ok()).toBeTruthy();

    // Wait for 2 images
    await expect(variantGrid).toHaveCount(2);

    // 4. Delete the old image (first one)
    // Assuming the first one is the old one (logo.png)
    const firstImageCard = variantGrid.first();
    // Try to find a delete button.
    const deleteButton = firstImageCard.locator('button').last();
    await deleteButton.click();
    await page.getByRole('button', { name: 'Xóa' }).click();

    await page.waitForLoadState('networkidle');

    // 5. Save changes
    const updateVariantBtn = page.getByRole('button', { name: 'Cập nhật biến thể' });
    console.error('Starting update flow...');
    const updateResponsePromise = page.waitForResponse(
      (resp) => resp.url().includes(`/api/variants/${variant.id}`) && resp.request().method().toUpperCase() === 'PUT'
    );

    console.error('Checking button state...');
    await expect(updateVariantBtn).toBeVisible();
    await expect(updateVariantBtn).toBeEnabled();

    console.error('Clicking update button...');
    await updateVariantBtn.click();

    const updateResp = await updateResponsePromise;
    expect(updateResp.ok()).toBeTruthy();
    await expect(page).toHaveURL(/\/products$/, { timeout: 20000 });

    // 6. Verify in list or detail that the image persisted
    // Go back to edit page to check persisted state
    await page.goto(`/products/variants/edit/${variant.id}`);
    // Should have exactly 1 image (old one was deleted, new one remains)
    await expect(variantGrid).toHaveCount(1);
    const img = variantGrid.first().locator('img');
    const src = await img.getAttribute('src');
    // Server renames uploaded files, so we just verify the old logo image is gone
    // and that we have an image URL (not null/empty)
    expect(src).toBeTruthy();
    expect(src).not.toContain('logo'); // The old image (initial upload) should be gone
    console.log('Final image src:', src);
  });

  // TODO: Add more test scenarios with proper auth sharing:
  // - Upload multiple images to variant
  // - Delete image from variant  
  // - Set primary image for variant
});
