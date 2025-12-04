import { test, expect } from '@playwright/test';

const mockProducts = {
  success: true,
  data: [
    { id: 1, code: 'SP001', name: 'Sản phẩm 1', stock: 10, cost_price: 50000, last_purchase_price: 48000, price: 100000 },
    { id: 2, code: 'SP002', name: 'Sản phẩm 2', stock: 0, cost_price: 20000, last_purchase_price: 18000, price: 40000 },
  ],
  pagination: { page: 1, limit: 20, total: 2, total_pages: 1 },
};

const mockPriceLists = {
  success: true,
  data: [
    { id: 1, name: 'Giá bán lẻ', type: 'retail', status: 'active' },
    { id: 2, name: 'Giá bán sỉ', type: 'wholesale', status: 'active' },
  ],
  pagination: { page: 1, limit: 100, total: 2, total_pages: 1 },
};

test.describe('Price lists module (Product Price List UI)', () => {
  test.beforeEach(async ({ page }) => {
    // Inject auth before any script runs
    await page.addInitScript(({ token, user }) => {
      window.__E2E_TEST__ = true;
      localStorage.setItem('lano_token', token);
      localStorage.setItem('lano_user', JSON.stringify(user));
    }, {
      token: 'mock-token',
      user: {
        id: 1,
        username: 'admin',
        permissions: [
          'products.view', 'products.create', 'products.edit',
          'price_lists.view', 'price_lists.create', 'price_lists.edit'
        ]
      },
    });

    // Mock products API
    await page.route('**/api/products*', async route => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(mockProducts) });
      }
      return route.continue();
    });

    // Mock price lists API
    await page.route('**/api/price-lists*', async route => {
      if (route.request().method() === 'GET') {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(mockPriceLists) });
      }
      if (route.request().method() === 'POST') {
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({ success: true, data: { id: 3, ...JSON.parse(route.request().postData() || '{}') } })
        });
      }
      return route.continue();
    });
  });

  test('shows price list page with product table', async ({ page }) => {
    await page.goto('/price-lists');

    // Check Header
    await expect(page.getByRole('heading', { name: 'Bảng giá chung' })).toBeVisible();

    // Check Table Columns
    await expect(page.getByRole('columnheader', { name: 'Mã hàng' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Tên hàng' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Tồn kho' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Giá vốn' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Giá nhập cuối' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Bảng giá chung' })).toBeVisible();

    // Check Data
    await expect(page.getByText('SP001')).toBeVisible();
    await expect(page.getByText('Sản phẩm 1')).toBeVisible();
    await expect(page.getByText('100.000đ')).toBeVisible();
  });

  test('opens create price list modal when clicking Create link in sidebar', async ({ page }) => {
    await page.goto('/price-lists');

    // Click "Tạo mới" link in sidebar
    await page.getByText('Tạo mới').click();

    // Check modal is visible
    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(page.getByRole('dialog').getByText('Tạo bảng giá')).toBeVisible();

    // Check tabs exist
    await expect(page.getByRole('tab', { name: 'Thông tin' })).toBeVisible();
    await expect(page.getByRole('tab', { name: 'Phạm vi áp dụng' })).toBeVisible();
  });

  test('modal Tab 1 shows correct form fields', async ({ page }) => {
    await page.goto('/price-lists');
    await page.getByText('Tạo mới').click();

    // Wait for modal
    await expect(page.getByRole('dialog')).toBeVisible();

    // Check Tab 1 fields - Thông tin
    await expect(page.getByPlaceholder('Nhập tên bảng giá')).toBeVisible();
    await expect(page.getByText('Hiệu lực', { exact: true })).toBeVisible();
    await expect(page.getByText('Công thức giá')).toBeVisible();
    await expect(page.getByText('Khi thu ngân lên đơn với bảng giá này')).toBeVisible();

    // Check status radio
    await expect(page.getByLabel('Áp dụng')).toBeVisible();
    await expect(page.getByLabel('Chưa áp dụng')).toBeVisible();
  });

  test('modal Tab 2 shows scope options', async ({ page }) => {
    await page.goto('/price-lists');
    await page.getByText('Tạo mới').click();

    // Wait for modal
    await expect(page.getByRole('dialog')).toBeVisible();

    // Click Tab 2
    await page.getByRole('tab', { name: 'Phạm vi áp dụng' }).click();

    // Check Tab 2 sections
    await expect(page.getByText('Chi nhánh')).toBeVisible();
    await expect(page.getByText('Nhóm khách hàng')).toBeVisible();
    await expect(page.getByText('Người tạo giao dịch')).toBeVisible();

    // Check radio options
    await expect(page.getByLabel('Toàn hệ thống')).toBeVisible();
    await expect(page.getByLabel('Chi nhánh cụ thể')).toBeVisible();
  });

  test('modal closes when clicking Bỏ qua', async ({ page }) => {
    await page.goto('/price-lists');
    await page.getByText('Tạo mới').click();

    // Wait for modal
    await expect(page.getByRole('dialog')).toBeVisible();

    // Click cancel button
    await page.getByRole('button', { name: 'Bỏ qua' }).click();

    // Modal should be closed
    await expect(page.getByRole('dialog')).not.toBeVisible();
  });

  test('modal validates required name field', async ({ page }) => {
    await page.goto('/price-lists');
    await page.getByText('Tạo mới').click();

    // Wait for modal
    await expect(page.getByRole('dialog')).toBeVisible();

    // Click save without entering name
    await page.getByRole('button', { name: 'Lưu' }).click();

    // Validation error should appear
    await expect(page.getByText('Vui lòng nhập tên bảng giá')).toBeVisible();
  });

  test('allows inline editing of price', async ({ page }) => {
    await page.goto('/price-lists');

    // Find the row for SP001 and click Edit
    const row = page.getByRole('row', { name: 'SP001' });
    await row.getByRole('button', { name: 'Sửa' }).click();

    // Check if input appears
    const input = row.locator('.ant-input-number-input');
    await expect(input).toBeVisible();
    await expect(input).toHaveValue('100,000');

    // Change value
    await input.fill('150000');

    await row.getByRole('button', { name: 'Lưu' }).click();

    // Check success message
    await expect(page.getByText('Cập nhật giá thành công')).toBeVisible();

    // Check if input is gone (editing mode off)
    await expect(input).not.toBeVisible();
  });

  test('sidebar filter elements are present', async ({ page }) => {
    await page.goto('/price-lists');

    // Check Sidebar Headers
    await expect(page.getByText('Bảng giá', { exact: true })).toBeVisible();
    await expect(page.getByText('Nhóm hàng', { exact: true })).toBeVisible();
    await expect(page.getByText('Tồn kho', { exact: true })).toBeVisible();
    await expect(page.getByText('Giá bán', { exact: true })).toBeVisible();

    // Check Selects
    await expect(page.getByText('Chọn bảng giá')).toBeVisible();
    await expect(page.getByText('Chọn nhóm hàng')).toBeVisible();
    await expect(page.getByText('Tất cả', { exact: true })).toBeVisible(); // Tồn kho default
  });

  test('price list dropdown loads from API', async ({ page }) => {
    await page.goto('/price-lists');

    // Click on price list dropdown
    // Finding the select inside the sidebar section for "Bảng giá"
    // The structure is <div><span>Bảng giá</span>...</div> <Select ... />
    // We can try clicking the text "Chọn bảng giá" if it's visible as placeholder
    await page.getByText('Chọn bảng giá').click();

    // Check API data is loaded
    await expect(page.getByText('Giá bán lẻ')).toBeVisible();
    await expect(page.getByText('Giá bán sỉ')).toBeVisible();
  });
});
