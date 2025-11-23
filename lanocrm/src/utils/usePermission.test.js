import { describe, it, expect, vi } from 'vitest';
import { renderHook } from '@testing-library/react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { usePermission } from './usePermission';
import React from 'react';

const createMockStore = (authState) => {
    return configureStore({
        reducer: {
            auth: () => authState,
        },
    });
};

const renderHookWithStore = (authState) => {
    const store = createMockStore(authState);
    const wrapper = ({ children }) => React.createElement(Provider, { store }, children);
    return renderHook(() => usePermission(), { wrapper });
};

describe('usePermission', () => {
    describe('isSuperAdmin', () => {
        it('should return true for super-admin role', () => {
            const authState = {
                user: { id: 1, role: 'super-admin' },
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.isSuperAdmin).toBe(true);
        });

        it('should return true for superadmin role', () => {
            const authState = {
                user: { id: 1, role: 'superadmin' },
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.isSuperAdmin).toBe(true);
        });

        it('should return true for SUPER-ADMIN (case insensitive)', () => {
            const authState = {
                user: { id: 1, role: 'SUPER-ADMIN' },
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.isSuperAdmin).toBe(true);
        });

        it('should return false for non-super-admin role', () => {
            const authState = {
                user: { id: 1, role: 'admin' },
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.isSuperAdmin).toBe(false);
        });

        it('should return false when user has no role', () => {
            const authState = {
                user: { id: 1 },
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.isSuperAdmin).toBe(false);
        });

        it('should return false when user is null', () => {
            const authState = {
                user: null,
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.isSuperAdmin).toBe(false);
        });
    });

    describe('hasPermission', () => {
        it('should return true when user has the permission', () => {
            const authState = {
                user: { id: 1, role: 'user' },
                permissions: ['users.view', 'users.edit'],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasPermission('users.view')).toBe(true);
        });

        it('should return false when user lacks the permission', () => {
            const authState = {
                user: { id: 1, role: 'user' },
                permissions: ['users.view'],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasPermission('users.delete')).toBe(false);
        });

        it('should return true for super-admin regardless of permissions', () => {
            const authState = {
                user: { id: 1, role: 'super-admin' },
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasPermission('any.permission')).toBe(true);
        });

        it('should return true when permission is null or empty', () => {
            const authState = {
                user: { id: 1, role: 'user' },
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasPermission(null)).toBe(true);
            expect(result.current.hasPermission('')).toBe(true);
        });

        it('should return false when permissions is not an array', () => {
            const authState = {
                user: { id: 1, role: 'user' },
                permissions: null,
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasPermission('users.view')).toBe(false);
        });
    });

    describe('hasAnyPermission', () => {
        it('should return true when user has at least one permission', () => {
            const authState = {
                user: { id: 1, role: 'user' },
                permissions: ['users.view'],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasAnyPermission(['users.view', 'users.edit'])).toBe(true);
        });

        it('should return false when user has none of the permissions', () => {
            const authState = {
                user: { id: 1, role: 'user' },
                permissions: ['products.view'],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasAnyPermission(['users.view', 'users.edit'])).toBe(false);
        });

        it('should return true for super-admin', () => {
            const authState = {
                user: { id: 1, role: 'super-admin' },
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasAnyPermission(['users.view', 'users.edit'])).toBe(true);
        });

        it('should return true when permission list is empty', () => {
            const authState = {
                user: { id: 1, role: 'user' },
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasAnyPermission([])).toBe(true);
        });

        it('should return true when permission list is not an array', () => {
            const authState = {
                user: { id: 1, role: 'user' },
                permissions: ['users.view'],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasAnyPermission(null)).toBe(true);
        });
    });

    describe('hasAllPermissions', () => {
        it('should return true when user has all permissions', () => {
            const authState = {
                user: { id: 1, role: 'user' },
                permissions: ['users.view', 'users.edit', 'users.delete'],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasAllPermissions(['users.view', 'users.edit'])).toBe(true);
        });

        it('should return false when user lacks some permissions', () => {
            const authState = {
                user: { id: 1, role: 'user' },
                permissions: ['users.view'],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasAllPermissions(['users.view', 'users.edit'])).toBe(false);
        });

        it('should return true for super-admin', () => {
            const authState = {
                user: { id: 1, role: 'super-admin' },
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasAllPermissions(['users.view', 'users.edit'])).toBe(true);
        });

        it('should return true when permission list is empty', () => {
            const authState = {
                user: { id: 1, role: 'user' },
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.hasAllPermissions([])).toBe(true);
        });
    });

    describe('userRole', () => {
        it('should return user role', () => {
            const authState = {
                user: { id: 1, role: 'admin' },
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.userRole).toBe('admin');
        });

        it('should return null when user has no role', () => {
            const authState = {
                user: { id: 1 },
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.userRole).toBeNull();
        });

        it('should return null when user is null', () => {
            const authState = {
                user: null,
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.userRole).toBeNull();
        });
    });

    describe('permissions', () => {
        it('should return all user permissions', () => {
            const authState = {
                user: { id: 1, role: 'user' },
                permissions: ['users.view', 'users.edit'],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.permissions).toEqual(['users.view', 'users.edit']);
        });

        it('should return empty array when permissions is null', () => {
            const authState = {
                user: { id: 1, role: 'user' },
                permissions: null,
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.permissions).toEqual([]);
        });
    });

    describe('user', () => {
        it('should return user object', () => {
            const mockUser = { id: 1, username: 'testuser', role: 'admin' };
            const authState = {
                user: mockUser,
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.user).toEqual(mockUser);
        });

        it('should return null when user is null', () => {
            const authState = {
                user: null,
                permissions: [],
            };

            const { result } = renderHookWithStore(authState);
            expect(result.current.user).toBeNull();
        });
    });
});
