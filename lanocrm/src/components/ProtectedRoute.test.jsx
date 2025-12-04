import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import ProtectedRoute from './ProtectedRoute';
import authReducer from '../store/slices/authSlice';

// Mock Navigate component
vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        Navigate: vi.fn(({ to }) => <div data-testid="navigate">{to}</div>),
    };
});

const createMockStore = (authState) => {
    return configureStore({
        reducer: {
            auth: () => authState,
        },
    });
};

const renderWithProviders = (component, authState) => {
    const store = createMockStore(authState);
    return render(
        <Provider store={store}>
            <BrowserRouter>
                {component}
            </BrowserRouter>
        </Provider>
    );
};

describe('ProtectedRoute', () => {
    const mockChild = <div data-testid="protected-content">Protected Content</div>;

    describe('authentication checks', () => {
        it('should redirect to login when not authenticated', () => {
            const authState = {
                isAuthenticated: false,
                user: null,
                permissions: [],
            };

            renderWithProviders(
                <ProtectedRoute>{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByTestId('navigate')).toHaveTextContent('/login');
            expect(screen.queryByTestId('protected-content')).not.toBeInTheDocument();
        });

        it('should render children when authenticated and no permission required', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'test' },
                permissions: [],
            };

            renderWithProviders(
                <ProtectedRoute>{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByTestId('protected-content')).toBeInTheDocument();
        });

        it('should render children when requireAuth is false', () => {
            const authState = {
                isAuthenticated: false,
                user: null,
                permissions: [],
            };

            renderWithProviders(
                <ProtectedRoute requireAuth={false}>{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByTestId('protected-content')).toBeInTheDocument();
        });
    });

    describe('permission checks', () => {
        it('should render children when user has required permission (string)', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'test' },
                permissions: ['users.view', 'users.edit'],
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission="users.view">{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByTestId('protected-content')).toBeInTheDocument();
        });

        it('should show permission denied when user lacks required permission (string)', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'test' },
                permissions: ['users.view'],
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission="users.delete">{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByText('⛔ Truy cập bị từ chối')).toBeInTheDocument();
            expect(screen.getByText('users.delete')).toBeInTheDocument();
            expect(screen.queryByTestId('protected-content')).not.toBeInTheDocument();
        });

        it('should render children when user has any required permission (array)', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'test' },
                permissions: ['users.view'],
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission={['users.view', 'users.edit']}>
                    {mockChild}
                </ProtectedRoute>,
                authState
            );

            expect(screen.getByTestId('protected-content')).toBeInTheDocument();
        });

        it('should show permission denied when user lacks all required permissions (array)', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'test' },
                permissions: ['products.view'],
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission={['users.view', 'users.edit']}>
                    {mockChild}
                </ProtectedRoute>,
                authState
            );

            expect(screen.getByText('⛔ Truy cập bị từ chối')).toBeInTheDocument();
            expect(screen.getByText('users.view')).toBeInTheDocument();
            expect(screen.getByText('users.edit')).toBeInTheDocument();
        });

        it('should support object format permissions', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'test' },
                permissions: [{ name: 'users.view' }, { name: 'users.edit' }],
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission="users.view">{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByTestId('protected-content')).toBeInTheDocument();
        });
    });

    describe('super-admin bypass', () => {
        it('should allow access for super-admin regardless of permissions', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'admin', role: 'super-admin' },
                permissions: [],
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission="any.permission">{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByTestId('protected-content')).toBeInTheDocument();
        });

        it('should allow access for superadmin (lowercase)', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'admin', role: 'superadmin' },
                permissions: [],
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission="any.permission">{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByTestId('protected-content')).toBeInTheDocument();
        });

        it('should allow access for SUPER-ADMIN (case insensitive)', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'admin', role: 'SUPER-ADMIN' },
                permissions: [],
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission="any.permission">{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByTestId('protected-content')).toBeInTheDocument();
        });
    });

    describe('edge cases', () => {
        it('should handle null permissions array', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'test' },
                permissions: null,
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission="users.view">{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByText('⛔ Truy cập bị từ chối')).toBeInTheDocument();
        });

        it('should handle undefined permissions', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'test' },
                permissions: undefined,
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission="users.view">{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByText('⛔ Truy cập bị từ chối')).toBeInTheDocument();
        });

        it('should handle empty permissions array', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'test' },
                permissions: [],
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission="users.view">{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByText('⛔ Truy cập bị từ chối')).toBeInTheDocument();
        });
    });

    describe('PermissionDenied component', () => {
        it('should render permission denied message', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'test' },
                permissions: [],
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission="users.view">{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByText('⛔ Truy cập bị từ chối')).toBeInTheDocument();
            expect(screen.getByText('Bạn không có quyền truy cập trang này.')).toBeInTheDocument();
        });

        it('should render required permissions list', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'test' },
                permissions: [],
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission={['users.view', 'users.edit']}>
                    {mockChild}
                </ProtectedRoute>,
                authState
            );

            expect(screen.getByText('Quyền cần có:')).toBeInTheDocument();
            expect(screen.getByText('users.view')).toBeInTheDocument();
            expect(screen.getByText('users.edit')).toBeInTheDocument();
        });

        it('should render back and home buttons', () => {
            const authState = {
                isAuthenticated: true,
                user: { id: 1, username: 'test' },
                permissions: [],
            };

            renderWithProviders(
                <ProtectedRoute requiredPermission="users.view">{mockChild}</ProtectedRoute>,
                authState
            );

            expect(screen.getByText('← Quay lại')).toBeInTheDocument();
            expect(screen.getByText('🏠 Trang chủ')).toBeInTheDocument();
        });
    });
});
