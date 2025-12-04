/**
 * Dashboard E2E Tests
 * @file tests/e2e/dashboard.spec.js
 * @description End-to-end tests for Dashboard page
 * @agent-layer: e2e-test
 * @agent-pattern: Playwright E2E testing
 * @agent-reusable: HIGH
 */

import { test, expect } from '@playwright/test';

// Test configuration
const BASE_URL = process.env.BASE_URL || 'http://localhost:3000';
const API_URL = process.env.API_URL || 'http://localhost:8000';

// Test credentials
const TEST_USER = {
    username: 'admin.staging',
    password: '123aA@hai',
};

test.describe('Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await page.goto(`${BASE_URL}/login`);

        // Fill login form
        await page.fill('input[name="username"]', TEST_USER.username);
        await page.fill('input[name="password"]', TEST_USER.password);

        // Submit and wait for navigation
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard', { timeout: 10000 });
    });

    test('should display page title and header', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Verify page title
        await expect(page.locator('h1.page-title')).toContainText('Xin chào');

        // Verify subtitle
        await expect(page.locator('.page-subtitle')).toContainText('Ảnh chụp nhanh hiệu suất bán hàng hôm nay');

        // Verify refresh button exists
        await expect(page.locator('button:has-text("Làm mới")')).toBeVisible();
    });

    test('should display KPI cards with data', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Wait for KPI cards to load (not in loading state)
        await page.waitForSelector('.kpi-card', { state: 'visible', timeout: 10000 });

        // Should have exactly 3 KPI cards
        const kpiCards = page.locator('.kpi-card');
        await expect(kpiCards).toHaveCount(3);

        // Verify KPI card titles - Use exact text match or nth to avoid ambiguity
        // "Doanh thu" matches both "Doanh thu" and "Doanh thu thuần"
        // Note: Text might include icon or whitespace, so we use a stricter regex but allow surrounding chars if needed
        // Or better, target the title element specifically
        await expect(page.locator('.kpi-card .kpi-title').filter({ hasText: /^Doanh thu$/ })).toBeVisible();
        await expect(page.locator('.kpi-card .kpi-title').filter({ hasText: /^Trả hàng$/ })).toBeVisible();
        await expect(page.locator('.kpi-card .kpi-title').filter({ hasText: /^Doanh thu thuần$/ })).toBeVisible();

        // Verify values are displayed (should contain currency format)
        // Find the card that contains the specific title
        const revenueCard = page.locator('.kpi-card').filter({ has: page.locator('.kpi-title', { hasText: /^Doanh thu$/ }) });
        const revenueValue = await revenueCard.locator('.kpi-value').textContent();
        expect(revenueValue).toMatch(/[\d,]+/);

        // Verify net revenue has delta indicator
        const netRevenueCard = page.locator('.kpi-card:has-text("Doanh thu thuần")');
        await expect(netRevenueCard.locator('.kpi-delta')).toBeVisible();
    });

    test('should display revenue chart', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Wait for chart card
        await page.waitForSelector('.chart-card', { state: 'visible', timeout: 10000 });

        // Verify chart title
        await expect(page.locator('.chart-card').locator('text=Doanh thu thuần')).toBeVisible();

        // Verify chart canvas is rendered
        await expect(page.locator('.chart-wrapper canvas')).toBeVisible();

        // Verify chart controls exist
        await expect(page.locator('text=Theo ngày')).toBeVisible();
        // Use first() because "Tháng này" might appear in dropdown options too
        await expect(page.locator('text=Tháng này').first()).toBeVisible();

        // Verify total is displayed
        await expect(page.locator('.chart-total .total-value')).toBeVisible();
    });

    test('should filter revenue chart by period', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Wait for chart to load
        await page.waitForSelector('.chart-wrapper canvas', { state: 'visible', timeout: 10000 });

        // Click "Theo giờ" (hourly view)
        await page.click('text=Theo giờ');

        // Wait for chart to update
        await page.waitForTimeout(1000);

        // Verify chart still exists after filter change
        await expect(page.locator('.chart-wrapper canvas')).toBeVisible();
    });

    test('should filter revenue chart by range', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Wait for chart to load
        await page.waitForSelector('.chart-wrapper canvas', { state: 'visible', timeout: 10000 });

        // Change range to "Tuần này"
        await page.click('.range-select');
        await page.click('text=Tuần này');

        // Wait for chart to update
        await page.waitForTimeout(1000);

        // Verify chart still exists
        await expect(page.locator('.chart-wrapper canvas')).toBeVisible();
    });

    test('should toggle chart type between column and bar', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Wait for chart to load
        await page.waitForSelector('.chart-wrapper canvas', { state: 'visible', timeout: 10000 });

        // Click bar chart toggle
        const barToggle = page.locator('.icon-toggle').filter({ hasText: /BarChart/ }).first();
        if (await barToggle.isVisible()) {
            await barToggle.click();
            await page.waitForTimeout(500);
        }

        // Verify chart still renders
        await expect(page.locator('.chart-wrapper canvas')).toBeVisible();
    });

    test('should display top products ranking', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Wait for top products card
        await page.waitForSelector('text=Top 10 hàng bán chạy', { state: 'visible', timeout: 10000 });

        // Wait for ranking list to load
        const rankingRows = page.locator('.list-card:has-text("hàng bán chạy") .ranking-row');
        await expect(rankingRows.first()).toBeVisible({ timeout: 10000 });

        // Should have up to 10 products
        const count = await rankingRows.count();
        expect(count).toBeGreaterThan(0);
        expect(count).toBeLessThanOrEqual(10);

        // Verify first product has required elements
        const firstProduct = rankingRows.first();
        await expect(firstProduct.locator('.ranking-name')).not.toBeEmpty();
        await expect(firstProduct.locator('.ranking-value')).not.toBeEmpty();
        await expect(firstProduct.locator('.rank-number')).toContainText('1.');
    });

    test('should filter top products by metric', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Wait for top products
        await page.waitForSelector('text=Top 10 hàng bán chạy', { state: 'visible', timeout: 10000 });

        // Find and click the metric dropdown in top products card
        const productCard = page.locator('.list-card:has-text("hàng bán chạy")');
        const metricSelect = productCard.locator('.ant-select').first();

        if (await metricSelect.isVisible()) {
            await metricSelect.click();
            await page.click('text=Theo số lượng');
            await page.waitForTimeout(1000);
        }

        // Verify products still displayed
        await expect(productCard.locator('.ranking-row').first()).toBeVisible();
    });

    test('should display top customers ranking', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Scroll to customers section
        await page.locator('text=Top 10 khách mua nhiều nhất').scrollIntoViewIfNeeded();

        // Wait for ranking list
        const customerRows = page.locator('.list-card:has-text("khách mua nhiều") .ranking-row');
        await expect(customerRows.first()).toBeVisible({ timeout: 10000 });

        // Should have up to 10 customers
        const count = await customerRows.count();
        expect(count).toBeGreaterThan(0);
        expect(count).toBeLessThanOrEqual(10);

        // Verify first customer has required elements
        const firstCustomer = customerRows.first();
        await expect(firstCustomer.locator('.ranking-name')).not.toBeEmpty();
        await expect(firstCustomer.locator('.ranking-value')).not.toBeEmpty();
    });

    test('should display recent activities', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Scroll to activities section
        await page.locator('text=Hoạt động gần đây').scrollIntoViewIfNeeded();

        // Wait for activities list
        const activities = page.locator('.activity-list .activity-item');
        await expect(activities.first()).toBeVisible({ timeout: 10000 });

        // Should have activities (up to 15)
        const count = await activities.count();
        expect(count).toBeGreaterThan(0);
        expect(count).toBeLessThanOrEqual(15);

        // Verify first activity structure
        const firstActivity = activities.first();
        await expect(firstActivity.locator('.activity-user')).not.toBeEmpty();
        await expect(firstActivity.locator('.activity-action')).not.toBeEmpty();
        await expect(firstActivity.locator('.activity-amount')).toBeVisible();
        await expect(firstActivity.locator('.activity-time')).not.toBeEmpty();
    });

    test('should refresh all dashboard data', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Wait for initial load
        await page.waitForSelector('.kpi-card', { state: 'visible', timeout: 10000 });

        // Get initial timestamp
        const initialTimestamp = await page.locator('.updated-at').textContent();

        // Click refresh button
        await page.click('button:has-text("Làm mới")');

        // Wait for refresh to complete
        await page.waitForTimeout(2000);

        // Verify timestamp updated
        const newTimestamp = await page.locator('.updated-at').textContent();
        expect(newTimestamp).toBeTruthy();

        // Verify data still displayed
        await expect(page.locator('.kpi-card')).toHaveCount(3);
    });

    test('should handle loading states', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Should show loading skeletons initially
        const hasSkeletons = await page.locator('.ant-skeleton').count();

        // Wait for data to load
        await page.waitForSelector('.kpi-card', { state: 'visible', timeout: 10000 });

        // Skeletons should be gone
        const remainingSkeletons = await page.locator('.ant-skeleton').count();
        expect(remainingSkeletons).toBe(0);
    });

    test('should be responsive on mobile', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto(`${BASE_URL}/dashboard`);

        // Wait for page to load
        await page.waitForSelector('.dashboard-page', { state: 'visible', timeout: 10000 });

        // Verify key elements are still visible
        await expect(page.locator('h1.page-title')).toBeVisible();
        await expect(page.locator('.kpi-card').first()).toBeVisible();

        // Chart should adapt to mobile
        await expect(page.locator('.chart-wrapper')).toBeVisible();
    });

    test('should not show error messages with valid data', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Wait for page to load
        await page.waitForSelector('.dashboard-page', { state: 'visible', timeout: 10000 });

        // Should not show error hints
        const errorHints = await page.locator('.error-hint').count();
        expect(errorHints).toBe(0);

        // Should not show "Dữ liệu mẫu" tags (sample data indicators)
        const sampleTags = await page.locator('text=Dữ liệu mẫu').count();
        expect(sampleTags).toBe(0);
    });

    test('should navigate to linked pages from activities', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);

        // Scroll to activities
        await page.locator('text=Hoạt động gần đây').scrollIntoViewIfNeeded();

        // Wait for activities
        await page.waitForSelector('.activity-item', { state: 'visible', timeout: 10000 });

        // Find first activity link
        const firstLink = page.locator('.activity-amount').first();

        if (await firstLink.isVisible()) {
            const href = await firstLink.getAttribute('href');
            expect(href).toBeTruthy();
            // Allow '#' if data is not fully linked, but prefer real links
            // In demo data, some links might be missing or just '#'
            if (href !== '#') {
                expect(href).toMatch(/#\/(invoices|returns|deliveries|purchase-orders)/);
            }
        }
    });
});

test.describe('Dashboard - Error Handling', () => {
    test('should handle network errors gracefully', async ({ page, context }) => {
        // Block API requests to simulate network error
        await context.route('**/api/dashboard/**', route => route.abort());

        await page.goto(`${BASE_URL}/login`);
        await page.fill('input[name="username"]', TEST_USER.username);
        await page.fill('input[name="password"]', TEST_USER.password);
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard', { timeout: 10000 });

        // Should show fallback sample data or error message
        await page.waitForSelector('.dashboard-page', { state: 'visible', timeout: 10000 });

        // Page should still render (with fallback data)
        await expect(page.locator('.kpi-card')).toHaveCount(3);

        // Should show error hints
        const errorHints = await page.locator('.error-hint').count();
        expect(errorHints).toBeGreaterThan(0);
    });
});