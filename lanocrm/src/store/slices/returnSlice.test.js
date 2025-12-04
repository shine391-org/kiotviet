import { describe, it, expect, vi, beforeEach } from 'vitest';
import returnReducer, {
  setReturnFilters,
  setReturnPage,
  fetchReturns,
  fetchReturnDetail,
} from './returnSlice';
import returnApi from '../../api/returnApi';

vi.mock('../../api/returnApi', () => ({
  default: {
    getReturns: vi.fn(),
    getReturn: vi.fn(),
  },
}));

describe('returnSlice', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('returns initial state', () => {
    const state = returnReducer(undefined, { type: 'unknown' });
    expect(state.filters.return_types).toContain('invoice');
    expect(state.items).toEqual([]);
  });

  it('setReturnFilters resets page to 1', () => {
    const prev = returnReducer(undefined, { type: 'unknown' });
    const next = returnReducer(prev, setReturnFilters({ status: ['cancelled'], page: 3 }));
    expect(next.filters.status).toEqual(['cancelled']);
    expect(next.filters.page).toBe(1);
  });

  it('setReturnPage updates pagination filters', () => {
    const prev = returnReducer(undefined, { type: 'unknown' });
    const next = returnReducer(prev, setReturnPage({ page: 2, limit: 50 }));
    expect(next.filters.page).toBe(2);
    expect(next.filters.limit).toBe(50);
  });

  it('fetchReturns stores data and page totals', async () => {
    const payload = {
      data: [
        { id: 1, return_code: 'TH0001', goods_amount: 1050000, need_refund: 1050000, refunded_amount: 0 },
        { id: 2, return_code: 'TH0002', goods_amount: 500000, need_refund: 450000, refunded_amount: 300000 },
      ],
      pagination: { page: 1, limit: 15, total: 2, total_pages: 1 },
      totals: { goods_total: 1550000, need_refund: 1500000, refunded: 300000 },
    };
    returnApi.getReturns.mockResolvedValue(payload);

    const dispatch = vi.fn();
    const thunk = fetchReturns();
    await thunk(dispatch, () => ({ returns: { filters: {} } }), undefined);

    expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
      type: fetchReturns.fulfilled.type,
      payload,
    }));

    const state = returnReducer(undefined, fetchReturns.fulfilled(payload));
    expect(state.items).toHaveLength(2);
    expect(state.pageTotals.goods_total).toBe(1550000);
    expect(state.totals.need_refund).toBe(1500000);
  });

  it('fetchReturnDetail stores current return', async () => {
    const payload = { data: { id: 5, return_code: 'TH0005' } };
    returnApi.getReturn.mockResolvedValue(payload);

    const dispatch = vi.fn();
    const thunk = fetchReturnDetail(5);
    await thunk(dispatch, () => ({}), undefined);

    expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
      type: fetchReturnDetail.fulfilled.type,
      payload,
    }));

    const state = returnReducer(undefined, fetchReturnDetail.fulfilled(payload));
    expect(state.current.return_code).toBe('TH0005');
  });
});
