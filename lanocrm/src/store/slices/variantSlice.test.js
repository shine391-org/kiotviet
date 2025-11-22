import { describe, it, expect, beforeEach } from 'vitest';
import variantReducer, { clearError } from './variantSlice';

describe('variantSlice', () => {
    let initialState;

    beforeEach(() => {
        initialState = {
            variants: [],
            currentVariant: null,
            loading: false,
            error: null
        };
    });

    it('should return initial state', () => {
        const state = variantReducer(undefined, { type: 'unknown' });
        expect(state).toMatchObject({
            variants: [],
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

