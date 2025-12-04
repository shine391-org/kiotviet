import { describe, it, expect, vi, beforeEach } from 'vitest';
import stockAuditReducer, {
  setStockAuditFilters,
  setStockAuditPage,
  fetchStockAudits,
  fetchStockAuditDetail,
} from './stockAuditSlice';
import stockAuditApi from '../../api/stockAuditApi';

vi.mock('../../api/stockAuditApi', () => ({
  default: {
    getAudits: vi.fn(),
    getAudit: vi.fn(),
  },
}));

describe('stockAuditSlice', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('returns initial state', () => {
    const state = stockAuditReducer(undefined, { type: 'unknown' });
    expect(state.items).toEqual([]);
    expect(state.filters.statuses).toContain('draft');
    expect(state.filters.page).toBe(1);
  });

  it('setStockAuditFilters resets page', () => {
    const prev = stockAuditReducer(undefined, { type: 'unknown' });
    const next = stockAuditReducer(prev, setStockAuditFilters({ search: 'SK0', page: 3 }));
    expect(next.filters.search).toBe('SK0');
    expect(next.filters.page).toBe(1);
  });

  it('setStockAuditPage updates pagination filters', () => {
    const prev = stockAuditReducer(undefined, { type: 'unknown' });
    const next = stockAuditReducer(prev, setStockAuditPage({ page: 2, limit: 25 }));
    expect(next.filters.page).toBe(2);
    expect(next.filters.limit).toBe(25);
  });

  it('fetchStockAudits stores list and summary', async () => {
    const payload = {
      data: [{ code: 'SK001', actualQuantity: 5 }],
      pagination: { page: 1, limit: 15, total: 1 },
      summary: { totalActualQuantity: 5 },
    };
    stockAuditApi.getAudits.mockResolvedValue(payload);

    const dispatch = vi.fn();
    const thunk = fetchStockAudits();
    await thunk(dispatch, () => ({ stockAudits: { filters: {} } }), undefined);

    expect(dispatch).toHaveBeenCalledWith(
      expect.objectContaining({ type: fetchStockAudits.fulfilled.type, payload })
    );

    const state = stockAuditReducer(undefined, fetchStockAudits.fulfilled(payload));
    expect(state.items).toHaveLength(1);
    expect(state.summary.totalActualQuantity).toBe(5);
  });

  it('fetchStockAuditDetail caches detail', async () => {
    const payload = { audit: { code: 'SK002', status: 'draft' } };
    stockAuditApi.getAudit.mockResolvedValue(payload);

    const dispatch = vi.fn();
    const thunk = fetchStockAuditDetail('SK002');
    await thunk(dispatch, () => ({}), undefined);

    expect(dispatch).toHaveBeenCalledWith(
      expect.objectContaining({ type: fetchStockAuditDetail.fulfilled.type, payload })
    );

    const state = stockAuditReducer(undefined, fetchStockAuditDetail.fulfilled(payload));
    expect(state.details.SK002.status).toBe('draft');
    expect(state.expandedCode).toBe('SK002');
  });
});
