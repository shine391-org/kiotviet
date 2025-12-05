import { describe, it, expect, vi, beforeEach } from 'vitest';
import userReducer, { clearError } from './userSlice';

describe('userSlice', () => {
    let initialState;

    beforeEach(() => {
        initialState = {
            users: [],
            currentUser: null,
            roles: [],
            branches: [],
            loading: false,
            error: null
        };
    });

    it('should return initial state', () => {
        const state = userReducer(undefined, { type: 'unknown' });
        expect(state).toMatchObject({
            users: [],
            loading: false,
            error: null
        });
    });

    describe('clearError', () => {
        it('should clear error', () => {
            const stateWithError = { ...initialState, error: 'Some error' };
            const state = userReducer(stateWithError, clearError());
            expect(state.error).toBeNull();
        });
    });

    describe('fetchUsers async thunk', () => {
        it('should set loading on pending', () => {
            const state = userReducer(initialState, { type: 'user/fetchUsers/pending' });
            expect(state.loading).toBe(true);
        });

        it('should set users on fulfilled', () => {
            const users = [{ id: '1', username: 'user1' }];
            const state = userReducer(initialState, {
                type: 'user/fetchUsers/fulfilled',
                payload: { data: users }
            });
            expect(state.loading).toBe(false);
        });

        it('should set error on rejected', () => {
            const error = 'Failed to fetch';
            const state = userReducer(initialState, {
                type: 'user/fetchUsers/rejected',
                payload: error
            });
            expect(state.loading).toBe(false);
            expect(state.error).toBe(error);
        });
    });
});
