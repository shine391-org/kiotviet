import { describe, it, expect } from 'vitest';
import reducer, { clearCustomerError, clearCurrentCustomer } from './customerSlice';

describe('customerSlice', () => {
  const initialState = {
    items: [],
    pagination: { page: 1, limit: 15, total: 0, total_pages: 0 },
    current: null,
    loading: false,
    currentLoading: false,
    saving: false,
    error: null,
    createSuccess: false,
    updateSuccess: false,
  };

  it('should return initial state', () => {
    const state = reducer(undefined, { type: 'unknown' });
    expect(state).toEqual(initialState);
  });

  it('should clear error', () => {
    const state = reducer({ ...initialState, error: 'Oops' }, clearCustomerError());
    expect(state.error).toBeNull();
  });

  it('should clear current customer', () => {
    const state = reducer({ ...initialState, current: { id: 1 } }, clearCurrentCustomer());
    expect(state.current).toBeNull();
  });

  it('sets loading on fetchCustomers pending', () => {
    const state = reducer(initialState, { type: 'customer/fetchAll/pending' });
    expect(state.loading).toBe(true);
  });

  it('stores list and pagination on fetchCustomers fulfilled', () => {
    const payload = { data: [{ id: 1 }], pagination: { page: 2, limit: 15, total: 1, total_pages: 1 } };
    const state = reducer(initialState, { type: 'customer/fetchAll/fulfilled', payload });
    expect(state.loading).toBe(false);
    expect(state.items).toEqual([{ id: 1 }]);
    expect(state.pagination.page).toBe(2);
  });

  it('sets error on fetchCustomer rejected', () => {
    const state = reducer(initialState, { type: 'customer/fetchOne/rejected', payload: 'Not found' });
    expect(state.currentLoading).toBe(false);
    expect(state.error).toBe('Not found');
  });

  it('marks create success', () => {
    const state = reducer(initialState, { type: 'customer/create/fulfilled', payload: { data: { id: 5 } } });
    expect(state.saving).toBe(false);
    expect(state.createSuccess).toBe(true);
    expect(state.current?.id).toBe(5);
  });
});
