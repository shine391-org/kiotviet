import { describe, it, expect, vi, beforeEach } from 'vitest';
import shipmentReducer, {
  setShipmentFilters,
  setShipmentPage,
  fetchShipments,
  fetchShipmentDetail,
} from './shipmentSlice';
import shipmentApi from '../../api/shipmentApi';

vi.mock('../../api/shipmentApi', () => ({
  default: {
    getShipments: vi.fn(),
    getShipment: vi.fn(),
  },
}));

describe('shipmentSlice', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('returns initial state', () => {
    const state = shipmentReducer(undefined, { type: 'unknown' });
    expect(state.filters.created_mode).toBe('month');
    expect(state.items).toEqual([]);
  });

  it('setShipmentFilters resets page to 1', () => {
    const prev = shipmentReducer(undefined, { type: 'unknown' });
    const next = shipmentReducer(prev, setShipmentFilters({ statuses: ['pending'], page: 3 }));
    expect(next.filters.statuses).toEqual(['pending']);
    expect(next.filters.page).toBe(1);
  });

  it('setShipmentPage updates pagination filters', () => {
    const prev = shipmentReducer(undefined, { type: 'unknown' });
    const next = shipmentReducer(prev, setShipmentPage({ page: 2, limit: 30 }));
    expect(next.filters.page).toBe(2);
    expect(next.filters.limit).toBe(30);
  });

  it('fetchShipments stores data and summary', async () => {
    const payload = {
      data: [{ id: 1, cod_amount: 100000 }],
      pagination: { page: 1, limit: 15, total: 1, total_pages: 1 },
      summary: { cod_total: 100000 },
    };
    shipmentApi.getShipments.mockResolvedValue(payload);

    const dispatch = vi.fn();
    const thunk = fetchShipments();
    await thunk(dispatch, () => ({ shipments: { filters: {} } }), undefined);

    expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
      type: fetchShipments.fulfilled.type,
      payload,
    }));

    const state = shipmentReducer(undefined, fetchShipments.fulfilled(payload));
    expect(state.items).toHaveLength(1);
    expect(state.summary.cod_total).toBe(100000);
    expect(state.pageTotals.cod_total).toBe(100000);
  });

  it('fetchShipmentDetail stores current', async () => {
    const payload = { data: { id: 2, code: 'SHP-2' } };
    shipmentApi.getShipment.mockResolvedValue(payload);

    const dispatch = vi.fn();
    const thunk = fetchShipmentDetail(2);
    await thunk(dispatch, () => ({}), undefined);

    expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
      type: fetchShipmentDetail.fulfilled.type,
      payload,
    }));

    const state = shipmentReducer(undefined, fetchShipmentDetail.fulfilled(payload));
    expect(state.current.id).toBe(2);
  });
});
