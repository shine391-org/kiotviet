import { describe, it, expect, vi, beforeEach } from 'vitest';
import { configureStore } from '@reduxjs/toolkit';
import authReducer, {
    loginUser,
    fetchCurrentUser,
    fetchUserPermissions,
    logoutUser,
    changePassword,
    logout,
    clearError,
    clearAuth,
    updatePermissions,
    updateUser,
    selectAuth,
    selectUser,
    selectPermissions,
    selectIsAuthenticated,
    selectAuthLoading,
    selectAuthError,
    selectHasPermission,
    selectHasAnyPermission,
    selectHasAllPermissions,
    selectIsSuperAdmin,
    selectUserRole,
    selectPermissionsByModule,
} from './authSlice';
import authApi from '../../api/authApi';

// Mock authApi
vi.mock('../../api/authApi', () => ({
    default: {
        login: vi.fn(),
        fetchCurrentUser: vi.fn(),
        getUserPermissions: vi.fn(),
        logout: vi.fn(),
        changePassword: vi.fn(),
        getUser: vi.fn(() => null),
        getToken: vi.fn(() => null),
        getPermissions: vi.fn(() => []),
        isAuthenticated: vi.fn(() => false),
        clearAuth: vi.fn(),
        saveUser: vi.fn(),
        savePermissions: vi.fn(),
    },
}));

describe('authSlice', () => {
    let store;

    beforeEach(() => {
        vi.clearAllMocks();
        // Mock console.log to avoid noise
        vi.spyOn(console, 'log').mockImplementation(() => { });
        vi.spyOn(console, 'error').mockImplementation(() => { });

        store = configureStore({
            reducer: {
                auth: authReducer,
            },
        });
    });

    describe('initial state', () => {
        it('should have correct initial state', () => {
            const state = store.getState().auth;
            expect(state.user).toBeNull();
            expect(state.token).toBeNull();
            expect(state.permissions).toEqual([]);
            expect(state.isAuthenticated).toBe(false);
            expect(state.loading).toBe(false);
            expect(state.error).toBeNull();
        });
    });

    describe('reducers', () => {
        it('should handle logout', () => {
            store.dispatch(logout());
            const state = store.getState().auth;

            expect(authApi.clearAuth).toHaveBeenCalled();
            expect(state.user).toBeNull();
            expect(state.token).toBeNull();
            expect(state.permissions).toEqual([]);
            expect(state.isAuthenticated).toBe(false);
            expect(state.error).toBeNull();
        });

        it('should handle clearError', () => {
            // Set error first
            store = configureStore({
                reducer: {
                    auth: authReducer,
                },
                preloadedState: {
                    auth: {
                        user: null,
                        token: null,
                        permissions: [],
                        isAuthenticated: false,
                        loading: false,
                        error: 'Some error',
                    },
                },
            });

            store.dispatch(clearError());
            const state = store.getState().auth;

            expect(state.error).toBeNull();
        });

        it('should handle clearAuth', () => {
            store.dispatch(clearAuth());
            const state = store.getState().auth;

            expect(authApi.clearAuth).toHaveBeenCalled();
            expect(state.user).toBeNull();
            expect(state.token).toBeNull();
            expect(state.permissions).toEqual([]);
            expect(state.isAuthenticated).toBe(false);
        });

        it('should handle updatePermissions', () => {
            const newPermissions = ['users.view', 'users.edit'];

            // Set user first
            store = configureStore({
                reducer: {
                    auth: authReducer,
                },
                preloadedState: {
                    auth: {
                        user: { id: 1, username: 'test' },
                        token: 'token',
                        permissions: [],
                        isAuthenticated: true,
                        loading: false,
                        error: null,
                    },
                },
            });

            store.dispatch(updatePermissions(newPermissions));
            const state = store.getState().auth;

            expect(state.permissions).toEqual(newPermissions);
            expect(state.user.permissions).toEqual(newPermissions);
            expect(authApi.saveUser).toHaveBeenCalled();
        });

        it('should handle updateUser', () => {
            // Set user first
            store = configureStore({
                reducer: {
                    auth: authReducer,
                },
                preloadedState: {
                    auth: {
                        user: { id: 1, username: 'test', email: 'old@example.com' },
                        token: 'token',
                        permissions: [],
                        isAuthenticated: true,
                        loading: false,
                        error: null,
                    },
                },
            });

            store.dispatch(updateUser({ email: 'new@example.com' }));
            const state = store.getState().auth;

            expect(state.user.email).toBe('new@example.com');
            expect(state.user.username).toBe('test');
            expect(authApi.saveUser).toHaveBeenCalled();
        });
    });

    describe('async thunks', () => {
        describe('loginUser', () => {
            it('should handle successful login', async () => {
                const mockResponse = {
                    success: true,
                    token: 'test-token',
                    user: {
                        id: 1,
                        username: 'testuser',
                        permissions: ['users.view'],
                    },
                };

                authApi.login.mockResolvedValue(mockResponse);

                await store.dispatch(loginUser({ username: 'testuser', password: 'password' }));
                const state = store.getState().auth;

                expect(state.loading).toBe(false);
                expect(state.isAuthenticated).toBe(true);
                expect(state.user).toEqual(mockResponse.user);
                expect(state.token).toBe(mockResponse.token);
                expect(state.permissions).toEqual(mockResponse.user.permissions);
                expect(state.error).toBeNull();
            });

            it('should handle login failure', async () => {
                const errorMessage = 'Invalid credentials';
                authApi.login.mockRejectedValue({
                    response: {
                        data: {
                            message: errorMessage,
                        },
                    },
                });

                await store.dispatch(loginUser({ username: 'testuser', password: 'wrong' }));
                const state = store.getState().auth;

                expect(state.loading).toBe(false);
                expect(state.isAuthenticated).toBe(false);
                expect(state.error).toBe(errorMessage);
            });

            it('should handle 401 error', async () => {
                authApi.login.mockRejectedValue({
                    response: {
                        status: 401,
                    },
                });

                await store.dispatch(loginUser({ username: 'testuser', password: 'wrong' }));
                const state = store.getState().auth;

                expect(state.error).toBe('Tên đăng nhập hoặc mật khẩu không đúng');
            });
        });

        describe('fetchCurrentUser', () => {
            it('should handle successful fetch', async () => {
                const mockUser = {
                    id: 1,
                    username: 'testuser',
                    permissions: ['users.view'],
                };

                authApi.fetchCurrentUser.mockResolvedValue(mockUser);

                await store.dispatch(fetchCurrentUser());
                const state = store.getState().auth;

                expect(state.loading).toBe(false);
                expect(state.user).toEqual(mockUser);
                expect(state.permissions).toEqual(mockUser.permissions);
                expect(state.isAuthenticated).toBe(true);
            });

            it('should handle fetch failure', async () => {
                const errorMessage = 'Unauthorized';
                authApi.fetchCurrentUser.mockRejectedValue({
                    response: {
                        data: {
                            message: errorMessage,
                        },
                    },
                });

                await store.dispatch(fetchCurrentUser());
                const state = store.getState().auth;

                expect(state.loading).toBe(false);
                expect(state.error).toBe(errorMessage);
            });
        });

        describe('fetchUserPermissions', () => {
            it('should handle successful permissions fetch', async () => {
                const mockPermissions = ['users.view', 'users.edit', 'products.view'];
                authApi.getUserPermissions.mockResolvedValue(mockPermissions);

                await store.dispatch(fetchUserPermissions());
                const state = store.getState().auth;

                expect(state.loading).toBe(false);
                expect(state.permissions).toEqual(mockPermissions);
                expect(authApi.savePermissions).toHaveBeenCalledWith(mockPermissions);
            });

            it('should handle permissions fetch failure', async () => {
                const errorMessage = 'Failed to fetch permissions';
                authApi.getUserPermissions.mockRejectedValue({
                    response: {
                        data: {
                            message: errorMessage,
                        },
                    },
                });

                await store.dispatch(fetchUserPermissions());
                const state = store.getState().auth;

                expect(state.loading).toBe(false);
                expect(state.error).toBe(errorMessage);
            });

            it('should handle generic error without response', async () => {
                authApi.getUserPermissions.mockRejectedValue(new Error('Network error'));

                await store.dispatch(fetchUserPermissions());
                const state = store.getState().auth;

                expect(state.loading).toBe(false);
                expect(state.error).toBe('Không thể lấy danh sách quyền');
            });
        });

        describe('changePassword', () => {
            it('should handle successful password change', async () => {
                const mockResponse = { success: true, message: 'Password changed' };
                authApi.changePassword.mockResolvedValue(mockResponse);

                const passwordData = {
                    oldPassword: 'old123',
                    newPassword: 'new456',
                };

                await store.dispatch(changePassword(passwordData));
                const state = store.getState().auth;

                expect(state.loading).toBe(false);
                expect(state.error).toBeNull();
                expect(authApi.changePassword).toHaveBeenCalledWith(passwordData);
            });

            it('should handle password change failure', async () => {
                const errorMessage = 'Old password is incorrect';
                authApi.changePassword.mockRejectedValue({
                    response: {
                        data: {
                            message: errorMessage,
                        },
                    },
                });

                const passwordData = {
                    oldPassword: 'wrong',
                    newPassword: 'new456',
                };

                await store.dispatch(changePassword(passwordData));
                const state = store.getState().auth;

                expect(state.loading).toBe(false);
                expect(state.error).toBe(errorMessage);
            });

            it('should handle generic error without response', async () => {
                authApi.changePassword.mockRejectedValue(new Error('Network error'));

                await store.dispatch(changePassword({ oldPassword: 'old', newPassword: 'new' }));
                const state = store.getState().auth;

                expect(state.loading).toBe(false);
                expect(state.error).toBe('Không thể đổi mật khẩu');
            });
        });

        describe('logoutUser', () => {
            it('should handle successful logout', async () => {
                authApi.logout.mockResolvedValue();

                // Set authenticated state first
                store = configureStore({
                    reducer: {
                        auth: authReducer,
                    },
                    preloadedState: {
                        auth: {
                            user: { id: 1 },
                            token: 'token',
                            permissions: ['users.view'],
                            isAuthenticated: true,
                            loading: false,
                            error: null,
                        },
                    },
                });

                await store.dispatch(logoutUser());
                const state = store.getState().auth;

                expect(state.user).toBeNull();
                expect(state.token).toBeNull();
                expect(state.permissions).toEqual([]);
                expect(state.isAuthenticated).toBe(false);
            });

            it('should force logout even if API fails', async () => {
                authApi.logout.mockRejectedValue(new Error('Network error'));

                // Set authenticated state first
                store = configureStore({
                    reducer: {
                        auth: authReducer,
                    },
                    preloadedState: {
                        auth: {
                            user: { id: 1 },
                            token: 'token',
                            permissions: ['users.view'],
                            isAuthenticated: true,
                            loading: false,
                            error: null,
                        },
                    },
                });

                await store.dispatch(logoutUser());
                const state = store.getState().auth;

                // Should still logout locally
                expect(state.user).toBeNull();
                expect(state.token).toBeNull();
                expect(state.isAuthenticated).toBe(false);
            });
        });
    });

    describe('selectors', () => {
        const mockState = {
            auth: {
                user: {
                    id: 1,
                    username: 'testuser',
                    role: 'admin',
                },
                token: 'test-token',
                permissions: ['users.view', 'users.edit', 'roles.view'],
                isAuthenticated: true,
                loading: false,
                error: null,
            },
        };

        it('should select auth state', () => {
            expect(selectAuth(mockState)).toEqual(mockState.auth);
        });

        it('should select user', () => {
            expect(selectUser(mockState)).toEqual(mockState.auth.user);
        });

        it('should select permissions', () => {
            expect(selectPermissions(mockState)).toEqual(mockState.auth.permissions);
        });

        it('should select isAuthenticated', () => {
            expect(selectIsAuthenticated(mockState)).toBe(true);
        });

        it('should select loading', () => {
            expect(selectAuthLoading(mockState)).toBe(false);
        });

        it('should select error', () => {
            expect(selectAuthError(mockState)).toBeNull();
        });

        it('should check if user has permission', () => {
            const hasPermission = selectHasPermission('users.view')(mockState);
            expect(hasPermission).toBe(true);

            const noPermission = selectHasPermission('products.delete')(mockState);
            expect(noPermission).toBe(false);
        });

        it('should return true for super-admin', () => {
            const superAdminState = {
                auth: {
                    user: { role: 'super-admin' },
                    permissions: [],
                },
            };

            const hasPermission = selectHasPermission('any.permission')(superAdminState);
            expect(hasPermission).toBe(true);
        });

        it('should check if user has any permission', () => {
            const hasAny = selectHasAnyPermission(['users.view', 'products.view'])(mockState);
            expect(hasAny).toBe(true);

            const hasNone = selectHasAnyPermission(['products.view', 'orders.view'])(mockState);
            expect(hasNone).toBe(false);
        });

        it('should check if user has all permissions', () => {
            const hasAll = selectHasAllPermissions(['users.view', 'users.edit'])(mockState);
            expect(hasAll).toBe(true);

            const notAll = selectHasAllPermissions(['users.view', 'products.delete'])(mockState);
            expect(notAll).toBe(false);
        });

        it('should check if user is super-admin', () => {
            const superAdminState = {
                auth: {
                    user: { role: 'super-admin' },
                },
            };

            expect(selectIsSuperAdmin(superAdminState)).toBe(true);
            expect(selectIsSuperAdmin(mockState)).toBe(false);
        });

        it('should select user role', () => {
            expect(selectUserRole(mockState)).toBe('admin');
        });

        it('should group permissions by module', () => {
            const grouped = selectPermissionsByModule(mockState);

            expect(grouped).toEqual({
                users: ['users.view', 'users.edit'],
                roles: ['roles.view'],
            });
        });
    });
});
