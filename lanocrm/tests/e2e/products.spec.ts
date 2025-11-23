import { test, expect } from '@playwright/test';

test.describe('Product List Page', () => {
  // Mock authenticated user
  test.beforeEach(async ({ page }) => {
    // Mock user in localStorage
    const mockUser = {
      id: 1,
      username: 'admin',
      role: 'super-admin',
      permissions: ['products.view', 'products.create', 'products.edit', 'products.delete']
    };

    const mockToken = 'mock-token-123';

    await page.addInitScript(({ user, token }) => {
      localStorage.setItem('lano_user', JSON.stringify(user));
      localStorage.setItem('lano_token', token);
    }, { user: mockUser, token: mockToken });
  });

  test('should list products', async ({ page }) => {
    // Mock products API
    await page.route('**/api/products*', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: [
            {
              id: 1,
              name: 'Product 1',
              code: 'P001',
              product_type: 'goods',
              status: 'active',
              variants: [
                { id: 1, sku: 'SKU001', price: 100000, stock_quantity: 10 }
              ]
            },
            {
              id: 2,
              name: 'Product 2',
              code: 'P002',
              product_type: 'goods',
              status: 'inactive',
               variants: [
                { id: 2, sku: 'SKU002', price: 200000, stock_quantity: 0 }
              ]
            }
          ],
          pagination: {
            page: 1,
            limit: 20,
            total: 2,
            total_pages: 1
          }
        })
      });
    });

    // Mock category tree (needed for filter)
    await page.route('**/api/categories/tree', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: []
        })
      });
    });

    // Mock categories list
    await page.route('**/api/categories*', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: []
        })
      });
    });

    // Mock attribute sets list
     await page.route('**/api/products/attributes/sets*', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: []
        })
      });
    });

    // Mock attributes list
     await page.route('**/api/products/attributes*', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: []
        })
      });
    });


    await page.goto('/products');

    // Verify title
    await expect(page.locator('h1')).toContainText('Danh sách hàng hóa');

    // Verify product list
    await expect(page.getByText('Product 1')).toBeVisible();
    await expect(page.getByText('P001')).toBeVisible();
    await expect(page.getByText('Product 2')).toBeVisible();
    await expect(page.getByText('P002')).toBeVisible();
  });

  test('should filter products by search', async ({ page }) => {
    // Mock products API with logic
    await page.route('**/api/products*', async route => {
        const url = new URL(route.request().url());
        const search = url.searchParams.get('search');

        if (search === 'SearchTerm') {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                    success: true,
                    data: [
                        {
                            id: 3,
                            name: 'Search Result Product',
                            code: 'P999',
                            product_type: 'goods',
                            status: 'active',
                            variants: []
                        }
                    ],
                    pagination: { total: 1, page: 1, limit: 20 }
                })
            });
        } else {
             await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                    success: true,
                    data: [],
                    pagination: { total: 0, page: 1, limit: 20 }
                })
            });
        }
    });

    // Mock category tree
    await page.route('**/api/categories/tree', async route => {
      await route.fulfill({ status: 200, body: JSON.stringify({ success: true, data: [] }) });
    });

    // Mock categories
    await page.route('**/api/categories*', async route => {
       await route.fulfill({ status: 200, body: JSON.stringify({ success: true, data: [] }) });
    });

     // Mock attribute sets list
     await page.route('**/api/products/attributes/sets*', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: []
        })
      });
    });

    // Mock attributes list
     await page.route('**/api/products/attributes*', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: []
        })
      });
    });


    await page.goto('/products');

    // Initially no products
    await expect(page.getByText('Search Result Product')).not.toBeVisible();

    const searchInput = page.getByPlaceholder('Tìm theo mã, tên, barcode...');
    await searchInput.fill('SearchTerm');

    // Verify result appears (implicit wait)
    await expect(page.getByText('Search Result Product')).toBeVisible();
  });
});
