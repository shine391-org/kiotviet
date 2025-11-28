---
title: "Frontend Testing Patterns - LANO CRM"
id: "FE-TESTING-PATTERNS-01"
version: "1.0"
status: "Active"
module: "Frontend Testing"
type: "Code Patterns"
tags: ["testing", "patterns", "frontend", "react", "vitest", "playwright", "unit-tests", "integration-tests"]
purpose: "Provides standard, copy-pasteable code patterns for frontend testing in LANO CRM (React)."
location: "docs/testing"
updated: "2025-11-25"
changes: "Added YAML frontmatter and standardized documentation structure. Updated MSW/vitest patterns (2025-11-28)."
related_to:
  - id: "FE-TESTING-GUIDE-01"
    description: "Refer to the main frontend testing guide for process and setup."
  - id: "FE-TEST-CHECKLIST-01"
    description: "Refer to this for mandatory frontend testing checklist."
---

# Frontend Testing Patterns

**COPY THESE PATTERNS TO START FAST**

## Pattern 1: Component Test (Unit)
**Use for**: UI Components, Props, Rendering.

```tsx
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Button } from './Button';

describe('Button Component', () => {
  it('renders correctly with props', () => {
    render(<Button variant="primary">Click Me</Button>);
    const btn = screen.getByRole('button', { name: /click me/i });
    expect(btn).toBeInTheDocument();
    expect(btn).toHaveClass('btn-primary');
  });

  it('handles click events', async () => {
    const handleClick = vi.fn();
    render(<Button onClick={handleClick}>Click Me</Button>);
    
    await userEvent.click(screen.getByRole('button'));
    expect(handleClick).toHaveBeenCalledTimes(1);
  });
});
```

## Pattern 2: Hook Test (Unit)
**Use for**: Custom hooks logic.

```tsx
import { renderHook, act } from '@testing-library/react';
import { useCounter } from './useCounter';

test('should increment counter', () => {
  const { result } = renderHook(() => useCounter());

  expect(result.current.count).toBe(0);

  act(() => {
    result.current.increment();
  });

  expect(result.current.count).toBe(1);
});
```

## Pattern 3: API Integration Test (Page/Form)
**Use for**: Forms, Data fetching, Loading states.

```tsx
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { LoginPage } from './LoginPage';
import { server } from '../../test/msw/server';
import { http, HttpResponse } from 'msw';

describe('LoginPage', () => {
  beforeEach(() => server.resetHandlers());

  it('handles successful login', async () => {
    server.use(http.post('/api/auth/login', () =>
      HttpResponse.json({ token: 'fake-token' })
    ));

    render(<LoginPage />);

    // Fill form
    await userEvent.type(screen.getByLabelText(/email/i), 'test@example.com');
    await userEvent.type(screen.getByLabelText(/password/i), 'password123');
    
    // Submit
    await userEvent.click(screen.getByRole('button', { name: /login/i }));

    // Assert Loading & Success
    expect(screen.getByText(/loading/i)).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByText(/dashboard/i)).toBeInTheDocument();
    });
  });

  it('displays validation errors', async () => {
    render(<LoginPage />);
    await userEvent.click(screen.getByRole('button', { name: /login/i }));
    
    expect(await screen.findByText(/email is required/i)).toBeInTheDocument();
  });

  it('shows API error', async () => {
    server.use(http.post('/api/auth/login', () =>
      HttpResponse.json({ message: 'Unauthorized' }, { status: 401 })
    ));
    render(<LoginPage />);
    await userEvent.type(screen.getByLabelText(/email/i), 'a@b.com');
    await userEvent.type(screen.getByLabelText(/password/i), 'wrong');
    await userEvent.click(screen.getByRole('button', { name: /login/i }));
    expect(await screen.findByText(/unauthorized/i)).toBeInTheDocument();
  });
});
```

## Pattern 4: E2E Test (Playwright)
**Use for**: Full user flows.

```ts
import { test, expect } from '@playwright/test';

test.describe('Authentication Flow', () => {
  test('user can login and logout', async ({ page }) => {
    // Login
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'Admin@123');
    await page.click('button[type="submit"]');

    // Verify Dashboard
    await expect(page).toHaveURL('/dashboard');
    await expect(page.getByText('Welcome, Admin')).toBeVisible();

    // Logout
    await page.click('#user-menu');
    await page.click('text=Logout');
    await expect(page).toHaveURL('/login');
  });
});
```

## Specific Scenarios

### Test Upload
```tsx
const file = new File(['hello'], 'hello.png', { type: 'image/png' });
const input = screen.getByLabelText(/upload/i);
await userEvent.upload(input, file);

expect(input.files[0]).toBe(file);
expect(input.files).toHaveLength(1);
```

### Test Error State
```tsx
server.use(
  http.get('/api/products', () =>
    HttpResponse.json({ message: 'Server Error' }, { status: 500 })
  )
);

render(<ProductList />);
expect(await screen.findByText(/server error/i)).toBeInTheDocument();
```

### Redux store helper (RTK)
```tsx
import { Provider } from 'react-redux';
import { setupStore } from '../../store';

const renderWithStore = (ui, { preloadedState } = {}) => {
  const store = setupStore(preloadedState);
  return render(<Provider store={store}>{ui}</Provider>);
};
```
