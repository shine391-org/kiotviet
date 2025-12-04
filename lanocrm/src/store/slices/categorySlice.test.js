import { describe, it, expect, beforeEach } from 'vitest';
import categoryReducer, {
    setCurrentCategory,
    clearCurrentCategory,
    resetSuccessFlags,
    clearError
} from './categorySlice';

describe('categorySlice', () => {
    let initialState;

    beforeEach(() => {
        initialState = {
            categories: [],
            categoryTree: [],
            productCountMap: {},
            currentCategory: null,
            categoryProducts: [],
            categoryProductsLoading: false,
            loading: false,
            createLoading: false,
            updateLoading: false,
            deleteLoading: false,
            error: null,
            createSuccess: false,
            updateSuccess: false,
            deleteSuccess: false
        };
    });

    it('should return initial state', () => {
        expect(categoryReducer(undefined, { type: 'unknown' })).toEqual(initialState);
    });

    describe('setCurrentCategory', () => {
        it('should set current category', () => {
            const category = { id: '1', name: 'Category 1' };
            const state = categoryReducer(initialState, setCurrentCategory(category));
            expect(state.currentCategory).toEqual(category);
        });
    });

    describe('clearCurrentCategory', () => {
        it('should clear current category', () => {
            const stateWithCategory = { ...initialState, currentCategory: { id: '1' } };
            const state = categoryReducer(stateWithCategory, clearCurrentCategory());
            expect(state.currentCategory).toBeNull();
        });
    });

    describe('resetSuccessFlags', () => {
        it('should reset all success flags', () => {
            const stateWithFlags = {
                ...initialState,
                createSuccess: true,
                updateSuccess: true,
                deleteSuccess: true
            };
            const state = categoryReducer(stateWithFlags, resetSuccessFlags());
            expect(state.createSuccess).toBe(false);
            expect(state.updateSuccess).toBe(false);
            expect(state.deleteSuccess).toBe(false);
        });
    });

    describe('clearError', () => {
        it('should clear error', () => {
            const stateWithError = { ...initialState, error: 'Some error' };
            const state = categoryReducer(stateWithError, clearError());
            expect(state.error).toBeNull();
        });
    });
});
