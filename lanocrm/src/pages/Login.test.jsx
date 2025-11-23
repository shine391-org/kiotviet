import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Login from './Login';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import authReducer, { loginUser } from '../store/slices/authSlice';
import { BrowserRouter } from 'react-router-dom';

// Mock authApi to prevent issues with initial state
vi.mock('../api/authApi', () => ({
  default: {
    getUser: vi.fn(() => null),
    getToken: vi.fn(() => null),
    getPermissions: vi.fn(() => []),
    isAuthenticated: vi.fn(() => false),
  }
}));

// Mock loginUser action
vi.mock('../store/slices/authSlice', async (importOriginal) => {
  const actual = await importOriginal();
  return {
    ...actual,
    loginUser: vi.fn(() => ({ type: 'auth/login/pending' })), // Mock thunk
    clearError: vi.fn(() => ({ type: 'auth/clearError' })),
  };
});

// Create a custom render function with Redux store
const renderWithProviders = (
  ui,
  {
    preloadedState = {},
    store = configureStore({
        reducer: { auth: authReducer },
        preloadedState: {
            auth: {
                user: null,
                token: null,
                permissions: [],
                isAuthenticated: false,
                loading: false,
                error: null,
                ...preloadedState.auth
            }
        }
    }),
    ...renderOptions
  } = {}
) => {
  function Wrapper({ children }) {
    return (
      <Provider store={store}>
        <BrowserRouter>{children}</BrowserRouter>
      </Provider>
    );
  }
  return { store, ...render(ui, { wrapper: Wrapper, ...renderOptions }) };
};

describe('Login Page', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders login form', () => {
    renderWithProviders(<Login />);
    expect(screen.getByPlaceholderText('Tên đăng nhập')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Mật khẩu')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /đăng nhập/i })).toBeInTheDocument();
  });

  it('updates input values', () => {
    renderWithProviders(<Login />);
    const usernameInput = screen.getByPlaceholderText('Tên đăng nhập');
    const passwordInput = screen.getByPlaceholderText('Mật khẩu');

    fireEvent.change(usernameInput, { target: { value: 'user1' } });
    fireEvent.change(passwordInput, { target: { value: 'pass1' } });

    expect(usernameInput.value).toBe('user1');
    expect(passwordInput.value).toBe('pass1');
  });

  it('disables submit button if inputs are empty', () => {
    renderWithProviders(<Login />);
    const submitButton = screen.getByRole('button', { name: /đăng nhập/i });
    expect(submitButton).toBeDisabled();
  });

  it('enables submit button when inputs are filled', () => {
    renderWithProviders(<Login />);
    fireEvent.change(screen.getByPlaceholderText('Tên đăng nhập'), { target: { value: 'user' } });
    fireEvent.change(screen.getByPlaceholderText('Mật khẩu'), { target: { value: 'pass' } });
    const submitButton = screen.getByRole('button', { name: /đăng nhập/i });
    expect(submitButton).not.toBeDisabled();
  });

  it('dispatches loginUser on submit', async () => {
    renderWithProviders(<Login />);
    fireEvent.change(screen.getByPlaceholderText('Tên đăng nhập'), { target: { value: 'user' } });
    fireEvent.change(screen.getByPlaceholderText('Mật khẩu'), { target: { value: 'pass' } });

    const submitButton = screen.getByRole('button', { name: /đăng nhập/i });
    fireEvent.click(submitButton);

    expect(loginUser).toHaveBeenCalledWith({ username: 'user', password: 'pass' });
  });

  it('displays error message when error state is present', () => {
     renderWithProviders(<Login />, {
         preloadedState: {
             auth: {
                 error: 'Invalid credentials',
                 loading: false,
                 isAuthenticated: false
             }
         }
     });
     expect(screen.getByText('❌ Invalid credentials')).toBeInTheDocument();
  });
});
