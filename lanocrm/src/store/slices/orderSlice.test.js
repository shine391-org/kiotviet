import { describe, it, expect, vi, beforeEach } from 'vitest';
import ordersReducer, {
  setOrderFilters,
  setOrderPage,
  fetchOrders,
  fetchOrderDetail,
} from './orderSlice';
import orderApi from '../../api/orderApi';
import { startOfYearIso } from '../../constants/orders';

vi.mock('../../api/orderApi', () => ({
  default: {
    getOrders: vi.fn(),
    getOrder: vi.fn(),
  },
}));

describe('orderSlice', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('returns initial state', () => {
    const state = ordersReducer(undefined, { type: 'unknown' });
    expect(state.filters.date_from).toBeDefined();
    expect(state.items).toEqual([]);
  });

  it('setOrderFilters resets page to 1', () => {
    const prev = ordersReducer(undefined, { type: 'unknown' });
    const next = ordersReducer(prev, setOrderFilters({ status: ['completed'], page: 3 }));
    expect(next.filters.status).toEqual(['completed']);
    expect(next.filters.page).toBe(1);
  });

  it('setOrderPage updates pagination filters', () => {
    const prev = ordersReducer(undefined, { type: 'unknown' });
    const next = ordersReducer(prev, setOrderPage({ page: 2, limit: 50 }));
    expect(next.filters.page).toBe(2);
    expect(next.filters.limit).toBe(50);
  });

  it('fetchOrders stores data and totals', async () => {
    const payload = {
      data: [{ id: 1, total: 100000, paid_amount: 50000, debt_amount: 50000 }],
      pagination: { page: 1, limit: 15, total: 1, total_pages: 1 },
      totals: { total_amount: 100000, paid_amount: 50000, debt_amount: 50000 },
    };
    orderApi.getOrders.mockResolvedValue(payload);

    const dispatch = vi.fn();
    const thunk = fetchOrders();
    await thunk(dispatch, () => ({ orders: { filters: {} } }), undefined);

    expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
      type: fetchOrders.fulfilled.type,
      payload,
    }));

    const state = ordersReducer(undefined, fetchOrders.fulfilled(payload));
    expect(state.items).toHaveLength(1);
    expect(state.totals.total_amount).toBe(100000);
    expect(state.pageTotals.total).toBe(100000);
  });

  it('fetchOrderDetail stores current order', async () => {
    const payload = { data: { id: 2, order_number: 'ORD-2' } };
    orderApi.getOrder.mockResolvedValue(payload);

    const dispatch = vi.fn();
    const thunk = fetchOrderDetail(2);
    await thunk(dispatch, () => ({}), undefined);

    expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
      type: fetchOrderDetail.fulfilled.type,
      payload,
    }));

    const state = ordersReducer(undefined, fetchOrderDetail.fulfilled(payload));
    expect(state.current.id).toBe(2);
  });
});
