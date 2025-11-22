import { describe, it, expect, beforeEach } from 'vitest';
import productReducer, {
    setFilters,
    resetFilters,
    setCurrentProduct,
    clearCurrentProduct,
    resetSuccessFlags,
    clearError,
    setPagination
} from './productSlice';

describe('productSlice', () => {
    let initialState;

    beforeEach(() => {
        initialState = {
            items: [],
            currentProduct: null,
            highlightedProductId: null,
            pagination: {
                page: 1,
                limit: 20,
                total: 0,
                total_pages: 0
            },
            filters: {
                search: '',
                category_id: null,
                product_type: null,
                status: null,
                is_active: null,
                brand: '',
                attributes: [],
                price_from: null,
                price_to: null,
                stock_from: null,
                stock_to: null,
                sort_by: 'p.created_at',
                order: 'desc'
            },
            loading: false,
            createLoading: false,
            updateLoading: false,
            deleteLoading: false,
            error: null,
            createSuccess: false,
            updateSuccess: false,
            deleteSuccess: false,
            categoryProducts: [],
            loadingCategoryProducts: false,
            errorCategoryProducts: null
        };
    });

    it('should return initial state', () => {
        const state = productReducer(undefined, { type: 'unknown' });
        expect(state).toMatchObject({
            items: [],
            currentProduct: null,
            loading: false
        });
    });

    describe('setFilters', () => {
        it('should set filters', () => {
            const filters = { search: 'test', category_id: '1' };
            const state = productReducer(initialState, setFilters(filters));
            expect(state.filters.search).toBe('test');
            expect(state.filters.category_id).toBe('1');
        });
    });

    describe('resetFilters', () => {
        it('should reset filters to initial state', () => {
            const stateWithFilters = {
                ...initialState,
                filters: { ...initialState.filters, search: 'test' }
            };
            const state = productReducer(stateWithFilters, resetFilters());
            expect(state.filters.search).toBe('');
        });
    });

    describe('setCurrentProduct', () => {
        it('should set current product', () => {
            const product = { id: '1', name: 'Product 1', code: 'P001' };
            const state = productReducer(initialState, setCurrentProduct(product));
            expect(state.currentProduct).toEqual(product);
        });
    });

    describe('clearCurrentProduct', () => {
        it('should clear current product', () => {
            const stateWithProduct = { ...initialState, currentProduct: { id: '1' } };
            const state = productReducer(stateWithProduct, clearCurrentProduct());
            expect(state.currentProduct).toBeNull();
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
            const state = productReducer(stateWithFlags, resetSuccessFlags());
            expect(state.createSuccess).toBe(false);
            expect(state.updateSuccess).toBe(false);
            expect(state.deleteSuccess).toBe(false);
        });
    });

    describe('clearError', () => {
        it('should clear error', () => {
            const stateWithError = { ...initialState, error: 'Some error' };
            const state = productReducer(stateWithError, clearError());
            expect(state.error).toBeNull();
        });
    });

    describe('setPagination', () => {
        it('should set pagination', () => {
            const pagination = { page: 2, limit: 20, total: 100 };
            const state = productReducer(initialState, setPagination(pagination));
            expect(state.pagination.page).toBe(2);
            expect(state.pagination.total).toBe(100);
        });
    });
});
