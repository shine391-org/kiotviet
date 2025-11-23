import { describe, it, expect, beforeEach } from 'vitest';
import variantReducer, { clearError } from './variantSlice';

describe('variantSlice', () => {
    let initialState;

    beforeEach(() => {
        initialState = {
            attributes: [],
            values: [],
            loading: false,
            error: null,
            syncStatus: null,
            usedOptionsMap: {}
        };
    });

    it('should return initial state', () => {
        const state = variantReducer(undefined, { type: 'unknown' });
        expect(state).toMatchObject({
            attributes: [],
            values: [],
            loading: false
        });
    });

    describe('clearError', () => {
        it('should clear error', () => {
            const stateWithError = { ...initialState, error: 'Some error' };
            const state = variantReducer(stateWithError, clearError());
            expect(state.error).toBeNull();
        });
    });
});

