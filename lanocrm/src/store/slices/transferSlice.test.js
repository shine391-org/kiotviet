import { describe, it, expect, vi, beforeEach } from 'vitest';
import transferReducer, {
  setTransferFilters,
  setTransferPage,
  fetchTransfers,
  fetchTransferDetail,
  duplicateTransfer,
  openTransfer,
  saveReceivingNotes,
} from './transferSlice';
import transferApi from '../../api/transferApi';

vi.mock('../../api/transferApi', () => ({
  default: {
    getTransfers: vi.fn(),
    getTransfer: vi.fn(),
    duplicateTransfer: vi.fn(),
    openTransfer: vi.fn(),
    saveReceivingNotes: vi.fn(),
  },
}));

describe('transferSlice', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('returns initial state', () => {
    const state = transferReducer(undefined, { type: 'unknown' });
    expect(state.items).toEqual([]);
    expect(state.filters.statuses).toContain('draft');
    expect(state.filters.page).toBe(1);
  });

  it('setTransferFilters resets page', () => {
    const prev = transferReducer(undefined, { type: 'unknown' });
    const next = transferReducer(prev, setTransferFilters({ search: 'TRF', page: 3 }));
    expect(next.filters.search).toBe('TRF');
    expect(next.filters.page).toBe(1);
  });

  it('setTransferPage updates pagination filters', () => {
    const prev = transferReducer(undefined, { type: 'unknown' });
    const next = transferReducer(prev, setTransferPage({ page: 2, limit: 30 }));
    expect(next.filters.page).toBe(2);
    expect(next.filters.limit).toBe(30);
  });

  it('fetchTransfers stores list and summary', async () => {
    const payload = {
      data: [{ code: 'TRF1', quantitySent: 2 }],
      pagination: { page: 1, limit: 15, total: 1 },
      summary: { totalQtySent: 2 },
    };
    transferApi.getTransfers.mockResolvedValue(payload);

    const dispatch = vi.fn();
    const thunk = fetchTransfers();
    await thunk(dispatch, () => ({ transfers: { filters: {} } }), undefined);

    expect(dispatch).toHaveBeenCalledWith(
      expect.objectContaining({ type: fetchTransfers.fulfilled.type, payload })
    );

    const state = transferReducer(undefined, fetchTransfers.fulfilled(payload));
    expect(state.items).toHaveLength(1);
    expect(state.summary.totalQtySent).toBe(2);
  });

  it('fetchTransferDetail caches detail', async () => {
    const payload = { transfer: { code: 'TRF2', status: 'draft' } };
    transferApi.getTransfer.mockResolvedValue(payload);

    const dispatch = vi.fn();
    const thunk = fetchTransferDetail('TRF2');
    await thunk(dispatch, () => ({}), undefined);

    expect(dispatch).toHaveBeenCalledWith(
      expect.objectContaining({ type: fetchTransferDetail.fulfilled.type, payload })
    );

    const state = transferReducer(undefined, fetchTransferDetail.fulfilled(payload));
    expect(state.details.TRF2.status).toBe('draft');
    expect(state.expandedCode).toBe('TRF2');
  });

  it('duplicateTransfer adds new row', () => {
    const payload = { transfer: { code: 'TRF3', status: 'draft' } };
    const prev = transferReducer(undefined, { type: 'unknown' });
    const next = transferReducer(prev, duplicateTransfer.fulfilled(payload));
    expect(next.items[0].code).toBe('TRF3');
    expect(next.pagination.total).toBe(prev.pagination.total + 1);
  });

  it('openTransfer updates status', () => {
    const startState = {
      ...transferReducer(undefined, { type: 'unknown' }),
      items: [{ code: 'TRF4', status: 'draft' }],
      details: { TRF4: { code: 'TRF4', status: 'draft' } },
    };
    const payload = { transfer: { code: 'TRF4', status: 'in_transit' } };
    const next = transferReducer(startState, openTransfer.fulfilled(payload));
    expect(next.items[0].status).toBe('in_transit');
    expect(next.details.TRF4.status).toBe('in_transit');
  });

  it('saveReceivingNotes updates note', () => {
    const startState = {
      ...transferReducer(undefined, { type: 'unknown' }),
      items: [{ code: 'TRF5', notes: '' }],
      details: { TRF5: { code: 'TRF5', receivingNotes: '' } },
    };
    const actionMeta = {
      type: saveReceivingNotes.fulfilled.type,
      meta: { arg: { code: 'TRF5', receivingNotes: 'Note' } },
    };
    const next = transferReducer(startState, actionMeta);
    expect(next.items[0].notes).toBe('Note');
    expect(next.details.TRF5.receivingNotes).toBe('Note');
  });
});
