import { describe, it, expect, beforeEach } from 'vitest';
import branchReducer, { clearError } from './branchSlice';

describe('branchSlice', () => {
    let initialState;

    beforeEach(() => {
        initialState = {
            branches: [],
            total: 0,
            loading: false,
            error: null
        };
    });

    it('should return initial state', () => {
        expect(branchReducer(undefined, { type: 'unknown' })).toEqual(initialState);
    });

    describe('clearError', () => {
        it('should clear error', () => {
            const stateWithError = { ...initialState, error: 'Some error' };
            const state = branchReducer(stateWithError, clearError());
            expect(state.error).toBeNull();
        });
    });

    describe('fetchBranches async thunk', () => {
        it('should set loading on pending', () => {
            const state = branchReducer(initialState, { type: 'branch/fetchBranches/pending' });
            expect(state.loading).toBe(true);
            expect(state.error).toBeNull();
        });

        it('should set branches on fulfilled', () => {
            const branches = [{ id: '1', name: 'Branch 1' }];
            const state = branchReducer(initialState, {
                type: 'branch/fetchBranches/fulfilled',
                payload: { data: branches, total: 1 }
            });
            expect(state.loading).toBe(false);
            expect(state.branches).toEqual(branches);
            expect(state.total).toBe(1);
        });

        it('should set error on rejected', () => {
            const error = 'Failed to fetch';
            const state = branchReducer(initialState, {
                type: 'branch/fetchBranches/rejected',
                payload: error
            });
            expect(state.loading).toBe(false);
            expect(state.error).toBe(error);
        });
    });
});
