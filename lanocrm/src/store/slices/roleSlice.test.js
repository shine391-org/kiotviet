import { describe, it, expect, vi, beforeEach } from 'vitest';
import roleReducer, { clearError } from './roleSlice';

describe('roleSlice', () => {
    let initialState;

    beforeEach(() => {
        initialState = {
            roles: [],
            currentRole: null,
            permissions: [],
            loading: false,
            error: null
        };
    });

    it('should return initial state', () => {
        const state = roleReducer(undefined, { type: 'unknown' });
        expect(state).toMatchObject({
            roles: [],
            loading: false,
            error: null
        });
    });

    describe('clearError', () => {
        it('should clear error', () => {
            const stateWithError = { ...initialState, error: 'Some error' };
            const state = roleReducer(stateWithError, clearError());
            expect(state.error).toBeNull();
        });
    });

    describe('fetchRoles async thunk', () => {
        it('should set loading on pending', () => {
            const state = roleReducer(initialState, { type: 'role/fetchRoles/pending' });
            expect(state.loading).toBe(true);
        });

        it('should set roles on fulfilled', () => {
            const roles = [{ id: '1', name: 'Admin' }];
            const state = roleReducer(initialState, {
                type: 'role/fetchRoles/fulfilled',
                payload: { data: roles }
            });
            expect(state.roles).toEqual(roles);
            expect(state.loading).toBe(false);
        });

        it('should set error on rejected', () => {
            const error = 'Failed to fetch';
            const state = roleReducer(initialState, {
                type: 'role/fetchRoles/rejected',
                payload: error
            });
            expect(state.loading).toBe(false);
            expect(state.error).toBe(error);
        });
    });
});
