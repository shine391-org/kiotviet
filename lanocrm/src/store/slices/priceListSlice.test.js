import { describe, it, expect } from 'vitest';
import reducer, { resetPriceListState } from './priceListSlice';

describe('priceListSlice', () => {
  const initialState = {
    items: [],
    pagination: { page: 1, limit: 20, total: 0, total_pages: 0 },
    current: null,
    currentItems: [],
    loading: false,
    saving: false,
    deleting: false,
    error: null,
    createSuccess: false,
    updateSuccess: false,
  };

  it('should return initial state', () => {
    const state = reducer(undefined, { type: 'unknown' });
    expect(state.items).toEqual([]);
  });

  it('should set loading on fetchPriceLists pending', () => {
    const state = reducer(initialState, { type: 'priceList/fetchAll/pending' });
    expect(state.loading).toBe(true);
  });

  it('should set items on fetchPriceLists fulfilled', () => {
    const payload = { data: [{ id: 1 }], pagination: { total: 1 } };
    const state = reducer(initialState, { type: 'priceList/fetchAll/fulfilled', payload });
    expect(state.items.length).toBe(1);
    expect(state.pagination.total).toBe(1);
  });

  it('should reset flags', () => {
    const stateWithFlags = { ...initialState, error: 'err', createSuccess: true };
    const state = reducer(stateWithFlags, resetPriceListState());
    expect(state.error).toBeNull();
    expect(state.createSuccess).toBe(false);
  });
});
