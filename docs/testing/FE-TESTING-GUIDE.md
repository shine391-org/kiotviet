---
title: "Frontend Testing Guide - LANO CRM"
id: "FE-TESTING-GUIDE-01"
version: "1.0"
status: "Active"
module: "Frontend Testing"
type: "Guideline"
tags: ["testing", "frontend", "react", "vitest", "playwright", "unit-tests", "integration-tests", "e2e-tests"]
purpose: "Provides a comprehensive guide to the frontend testing strategy, tools, and workflow for LANO CRM (React)."
location: "docs/testing"
related_to:
  - id: "FE-TESTING-PATTERNS-01"
    description: "Refer to this for FE code examples and patterns to copy."
---

# Frontend Testing Guide

## 1. Test Pyramid (Frontend)
We follow a balanced testing strategy for the Frontend (React):

- **Unit Tests (Vitest)**: Test individual components, hooks, and utility functions in isolation.
- **Integration Tests (Vitest + RTL)**: Test how components interact with each other and the store/API.
- **E2E Tests (Playwright)**: Test full user flows (Login, Checkout) on a real browser.

## 2. Test Types & Tools

### Unit Tests
- **Tool**: Vitest
- **Focus**: Logic, Props, State changes.
- **Example**:
```tsx
import { render, screen } from '@testing-library/react';
import Button from './Button';

test('renders button with text', () => {
  render(<Button>Click me</Button>);
  expect(screen.getByText('Click me')).toBeInTheDocument();
});
```

### Integration Tests
- **Tool**: Vitest + React Testing Library + MSW (Mock Service Worker)
- **Focus**: Form submission, API loading states, Redux integration.
- **Example**:
```tsx
test('submits form and shows success', async () => {
  render(<LoginForm />);
  await userEvent.type(screen.getByLabelText('Email'), 'user@example.com');
  await userEvent.click(screen.getByRole('button', { name: /login/i }));
  expect(await screen.findByText('Welcome back!')).toBeInTheDocument();
});
```

### E2E Tests
- **Tool**: Playwright
- **Focus**: Critical paths, Cross-browser compatibility.
- **Example**:
```ts
test('user can login', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'user@example.com');
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL('/dashboard');
});
```

## 3. Coverage Requirements
- **Target**: >= 70% Statements/Branches.
- **Critical Components**: 100% (Auth, Payments, Utils).
- **Check Coverage**:
```bash
npm run test:coverage
```
- **If Fail**: Identify missing branches in the report (`coverage/index.html`) and add test cases.

## 4. Testing Workflow

### Running Tests
- **Unit/Integration**: `npm test` (Fast, watch mode)
- **Coverage**: `npm run test:coverage` (Full report)
- **E2E**: `npm run test:e2e` (Real browser)

### Manual Smoke Test (Required before PR)
1. **UI Check**: Open app in Chrome. Verify layout, fonts, images.
2. **Console**: Check DevTools Console for Red/Yellow errors.
3. **Navigation**: Click all menu links. Verify URL changes.
4. **Forms**: Try submitting empty forms (validation) and valid forms.
5. **Uploads**: Test file upload if applicable.

## 5. Best Practices
- **Test User Behavior**: Use `getByRole`, `getByLabelText` (like a user would). Avoid `getByTestId` unless necessary.
- **Mock Externalities**: Mock API calls using MSW or `vi.mock`. Never call real APIs in Unit/Integration tests.
- **Keep it Simple**: Tests should be readable documentation.
- **Clean Up**: Reset mocks in `afterEach`.

**See Patterns**: [FE-TESTING-PATTERNS.md](./FE-TESTING-PATTERNS.md)
