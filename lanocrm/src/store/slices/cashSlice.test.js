import { describe, it, expect, vi, beforeEach } from 'vitest';
import cashReducer, {
  setCashFilters,
  fetchCashTransactions,
  fetchCashSummary,
  createCashReceipt,
  fetchCashBalance,
} from './cashSlice';
import cashApi from '../../api/cashApi';
import { startOfMonth, format } from 'date-fns';

vi.mock('../../api/cashApi', () => ({
  default: {
    getTransactions: vi.fn(),
    getTransaction: vi.fn(),
    createReceipt: vi.fn(),
    createPayment: vi.fn(),
    deleteTransaction: vi.fn(),
    getBalance: vi.fn(),
    getDailyReport: vi.fn(),
  },
}));

describe('cashSlice', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('returns initial state', () => {
    const state = cashReducer(undefined, { type: 'unknown' });
    const expectedDateFrom = format(startOfMonth(new Date()), 'yyyy-MM-dd');
    expect(state.items).toEqual([]);
    expect(state.filters.date_from).toBe(expectedDateFrom);
    expect(state.filters.page).toBe(1);
  });

  it('setCashFilters updates filters and resets page', () => {
    const prevState = cashReducer(undefined, { type: 'unknown' });
    const nextState = cashReducer(prevState, setCashFilters({ type: 'RECEIPT', page: 3 }));
    expect(nextState.filters.type).toBe('RECEIPT');
    expect(nextState.filters.page).toBe(1);
  });

  it('fetchCashTransactions thunk dispatches fulfilled action', async () => {
    const mockPayload = {
      data: [{ id: 1, type: 'RECEIPT', amount: 100000 }],
      pagination: { page: 1, limit: 20, total: 1, total_pages: 1 },
    };
    cashApi.getTransactions.mockResolvedValue(mockPayload);

    const dispatch = vi.fn();
    const thunk = fetchCashTransactions({ page: 1 });
    await thunk(dispatch, () => ({}), undefined);

    expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
      type: fetchCashTransactions.fulfilled.type,
      payload: mockPayload,
    }));
  });

  it('reducer stores fetched transactions and page totals', () => {
    const mockPayload = {
      data: [
        { id: 1, type: 'RECEIPT', amount: 100000 },
        { id: 2, type: 'PAYMENT', amount: 20000 },
      ],
      pagination: { page: 1, limit: 20, total: 2, total_pages: 1 },
    };

    const nextState = cashReducer(undefined, fetchCashTransactions.fulfilled(mockPayload));
    expect(nextState.items).toHaveLength(2);
    expect(nextState.pageTotals.receipt).toBe(100000);
    expect(nextState.pageTotals.payment).toBe(20000);
  });

  it('fetchCashSummary aggregates totals', async () => {
    const mockResponse = {
      data: [
        { id: 1, type: 'RECEIPT', amount: 50000 },
        { id: 2, type: 'PAYMENT', amount: 10000 },
      ],
      pagination: { total: 2 },
    };
    cashApi.getTransactions.mockResolvedValue(mockResponse);

    const dispatch = vi.fn();
    const thunk = fetchCashSummary({ filters: { page: 1, limit: 20 } });
    await thunk(dispatch, () => ({}), undefined);

    expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
      type: fetchCashSummary.fulfilled.type,
      payload: expect.objectContaining({
        totals: { receipt: 50000, payment: 10000 },
      }),
    }));
  });

  it('createCashReceipt inserts new record into state on fulfilled', () => {
    const initial = cashReducer(undefined, { type: 'unknown' });
    const payload = {
      data: { id: 10, type: 'RECEIPT', amount: 123000 },
    };
    const next = cashReducer(initial, createCashReceipt.fulfilled(payload));
    expect(next.items[0].id).toBe(10);
    expect(next.pagination.total).toBe(1);
  });

  it('fetchCashBalance passes filters', async () => {
    cashApi.getBalance.mockResolvedValue({ data: { balance: 10 } });

    const dispatch = vi.fn();
    const thunk = fetchCashBalance({ branchId: 1, filters: { date_from: '2025-01-01' } });
    await thunk(dispatch, () => ({}), undefined);

    expect(cashApi.getBalance).toHaveBeenCalledWith(1, { date_from: '2025-01-01' });
  });
});
