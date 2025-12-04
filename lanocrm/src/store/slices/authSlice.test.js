import { describe, it, expect, vi, beforeEach } from 'vitest';
import authReducer, {
  loginUser,
  logoutUser,
  selectHasPermission,
  clearError
} from './authSlice';
import authApi from '../../api/authApi';

// Mock authApi
vi.mock('../../api/authApi', () => ({
  default: {
    login: vi.fn(),
    fetchCurrentUser: vi.fn(),
    getUserPermissions: vi.fn(),
    logout: vi.fn(),
    getUser: vi.fn(() => null),
    getToken: vi.fn(() => null),
    getPermissions: vi.fn(() => []),
    isAuthenticated: vi.fn(() => false),
    clearAuth: vi.fn(),
    saveUser: vi.fn(),
    savePermissions: vi.fn(),
    changePassword: vi.fn(),
  }
}));

describe('authSlice', () => {
  const initialState = {
    user: null,
    token: null,
    permissions: [],
    isAuthenticated: false,
    loading: false,
    error: null,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('reducers', () => {
    it('should handle initial state', () => {
      expect(authReducer(undefined, { type: 'unknown' })).toEqual(initialState);
    });

    it('should handle clearError', () => {
         const errorState = {
             ...initialState,
             error: 'some error'
         };
         const nextState = authReducer(errorState, clearError());
         expect(nextState.error).toBeNull();
    });
  });

  describe('async thunks', () => {
    describe('loginUser', () => {
      it('should handle successful login', async () => {
        const mockResponse = {
          success: true,
          token: 'fake-token',
          user: { id: 1, name: 'User', permissions: ['read'] }
        };
        authApi.login.mockResolvedValue(mockResponse);

        const dispatch = vi.fn();
        const thunk = loginUser({ username: 'user', password: 'pw' });

        await thunk(dispatch, () => {}, undefined);

        expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
             type: loginUser.pending.type
        }));
        expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
             type: loginUser.fulfilled.type,
             payload: {
                 success: true,
                 token: 'fake-token',
                 user: mockResponse.user
             }
        }));
      });

      it('should handle failed login', async () => {
          const errorResponse = {
              response: {
                  data: {
                      message: 'Invalid credentials'
                  }
              }
          };
          authApi.login.mockRejectedValue(errorResponse);

          const dispatch = vi.fn();
          const thunk = loginUser({ username: 'user', password: 'pw' });

          await thunk(dispatch, () => {}, undefined);

          expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
             type: loginUser.rejected.type,
             payload: 'Invalid credentials'
          }));
      });
    });

    describe('logoutUser', () => {
      it('should call authApi.logout and clearAuth then reset state', async () => {
        authApi.logout.mockResolvedValue();
        const dispatch = vi.fn();
        const thunk = logoutUser();

        await thunk(dispatch, () => initialState, undefined);

        expect(authApi.logout).toHaveBeenCalled();
        expect(authApi.clearAuth).toHaveBeenCalled();
        expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({ type: logoutUser.pending.type }));
        expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({ type: logoutUser.fulfilled.type }));
      });
    });
  });

  describe('selectors', () => {
      it('selectHasPermission should return true if permission exists', () => {
          const state = {
              auth: {
                  user: { role: 'user' },
                  permissions: ['users.view']
              }
          };
          expect(selectHasPermission('users.view')(state)).toBe(true);
      });

      it('selectHasPermission should return false if permission does not exist', () => {
          const state = {
              auth: {
                  user: { role: 'user' },
                  permissions: ['users.view']
              }
          };
          expect(selectHasPermission('users.delete')(state)).toBe(false);
      });

      it('selectHasPermission should return true for super-admin', () => {
           const state = {
              auth: {
                  user: { role: 'super-admin' },
                  permissions: []
              }
          };
          expect(selectHasPermission('any.permission')(state)).toBe(true);
      });
  });
});
