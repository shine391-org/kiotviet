import { test, expect } from '@playwright/test';

test.describe('Login Flow', () => {
  test('should login successfully with valid credentials', async ({ page }) => {
    // Navigate to login page
    await page.goto('/login');

    // Fill in credentials
    await page.fill('input[name="username"]', 'devadmin');
    await page.fill('input[name="password"]', '123aA@hai');

    // Click login button
    await page.click('button[type="submit"]');

    // Wait for navigation to dashboard or home page
    // Adjust the URL or selector based on actual application behavior
    await expect(page).toHaveURL('/dashboard');

    // Verify successful login by checking for an element that only appears when logged in
    // For example, a user menu or logout button
    // await expect(page.locator('.user-menu')).toBeVisible();
  });

  test('should show error with invalid credentials', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="username"]', 'wronguser');
    await page.fill('input[name="password"]', 'wrongpass');
    await page.click('button[type="submit"]');

    // Verify error message
    // Adjust selector and text based on actual application behavior
    // await expect(page.locator('.error-message')).toBeVisible();
  });
});
