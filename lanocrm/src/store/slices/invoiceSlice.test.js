import { describe, it, expect, vi, beforeEach } from 'vitest';
import invoicesReducer, {
  setInvoiceFilters,
  setInvoicePage,
  fetchInvoices,
  fetchInvoiceDetail,
} from './invoiceSlice';
import invoiceApi from '../../api/invoiceApi';

vi.mock('../../api/invoiceApi', () => ({
  default: {
    getInvoices: vi.fn(),
    getInvoice: vi.fn(),
  },
}));

describe('invoiceSlice', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('returns initial state', () => {
    const state = invoicesReducer(undefined, { type: 'unknown' });
    expect(state.filters.invoice_types).toContain('delivery');
    expect(state.items).toEqual([]);
  });

  it('setInvoiceFilters resets page to 1', () => {
    const prev = invoicesReducer(undefined, { type: 'unknown' });
    const next = invoicesReducer(prev, setInvoiceFilters({ invoice_status: ['completed'], page: 3 }));
    expect(next.filters.invoice_status).toEqual(['completed']);
    expect(next.filters.page).toBe(1);
  });

  it('setInvoicePage updates pagination filters', () => {
    const prev = invoicesReducer(undefined, { type: 'unknown' });
    const next = invoicesReducer(prev, setInvoicePage({ page: 2, limit: 50 }));
    expect(next.filters.page).toBe(2);
    expect(next.filters.limit).toBe(50);
  });

  it('fetchInvoices stores data and page totals', async () => {
    const payload = {
      data: [
        { id: 1, invoice_code: 'HD001', customer_payable: 100000, customer_paid: 80000, cod_amount: 20000, shipping_fee: 15000 },
      ],
      pagination: { page: 1, limit: 15, total: 1, total_pages: 1 },
      totals: { customer_payable: 200000, customer_paid: 150000, cod_amount: 50000, shipping_fee: 20000 },
    };
    invoiceApi.getInvoices.mockResolvedValue(payload);

    const dispatch = vi.fn();
    const thunk = fetchInvoices();
    await thunk(dispatch, () => ({ invoices: { filters: {} } }), undefined);

    expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
      type: fetchInvoices.fulfilled.type,
      payload,
    }));

    const state = invoicesReducer(undefined, fetchInvoices.fulfilled(payload));
    expect(state.items).toHaveLength(1);
    expect(state.pageTotals.customer_payable).toBe(100000);
    expect(state.totals.customer_paid).toBe(150000);
  });

  it('fetchInvoiceDetail stores current invoice', async () => {
    const payload = { data: { id: 2, invoice_code: 'HD002' } };
    invoiceApi.getInvoice.mockResolvedValue(payload);

    const dispatch = vi.fn();
    const thunk = fetchInvoiceDetail(2);
    await thunk(dispatch, () => ({}), undefined);

    expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
      type: fetchInvoiceDetail.fulfilled.type,
      payload,
    }));

    const state = invoicesReducer(undefined, fetchInvoiceDetail.fulfilled(payload));
    expect(state.current.invoice_code).toBe('HD002');
  });
});
