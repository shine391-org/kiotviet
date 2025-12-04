import reducer, {
  clearDashboardError,
  fetchKpiToday,
  fetchTopProducts,
  setRevenueFilters,
  setTopProductsFilters,
} from './dashboardSlice';

describe('dashboardSlice', () => {
  const baseState = reducer(undefined, { type: 'unknown' });

  it('returns initial state', () => {
    expect(baseState).toMatchObject({
      kpi: expect.any(Object),
      revenue: expect.any(Object),
      topProducts: expect.any(Object),
      topCustomers: expect.any(Object),
      activities: expect.any(Object),
      lastUpdated: null,
    });
  });

  it('updates revenue filters', () => {
    const nextState = reducer(
      baseState,
      setRevenueFilters({ range: 'week', period: 'hour' })
    );

    expect(nextState.revenue.filters.range).toBe('week');
    expect(nextState.revenue.filters.period).toBe('hour');
  });

  it('stores KPI data on fulfilled', () => {
    const action = {
      type: fetchKpiToday.fulfilled.type,
      payload: { data: { revenue: 500000, netRevenue: 400000, netChange: 5 } },
    };
    const nextState = reducer(baseState, action);

    expect(nextState.kpi.data.revenue).toBe(500000);
    expect(nextState.kpi.data.netRevenue).toBe(400000);
    expect(nextState.lastUpdated).toBeTruthy();
  });

  it('sets error on top products rejected', () => {
    const action = {
      type: fetchTopProducts.rejected.type,
      payload: 'Lỗi tải top products',
    };
    const nextState = reducer(baseState, action);

    expect(nextState.topProducts.error).toBe('Lỗi tải top products');
    expect(nextState.topProducts.loading).toBe(false);
  });

  it('clears errors', () => {
    const stateWithError = {
      ...baseState,
      kpi: { ...baseState.kpi, error: 'err' },
      revenue: { ...baseState.revenue, error: 'err' },
      topProducts: { ...baseState.topProducts, error: 'err' },
      topCustomers: { ...baseState.topCustomers, error: 'err' },
      activities: { ...baseState.activities, error: 'err' },
    };

    const nextState = reducer(stateWithError, clearDashboardError());

    expect(nextState.kpi.error).toBeNull();
    expect(nextState.revenue.error).toBeNull();
    expect(nextState.topProducts.error).toBeNull();
    expect(nextState.topCustomers.error).toBeNull();
    expect(nextState.activities.error).toBeNull();
  });

  it('updates top product filters', () => {
    const nextState = reducer(
      baseState,
      setTopProductsFilters({ metric: 'quantity', range: 'today' })
    );

    expect(nextState.topProducts.filters.metric).toBe('quantity');
    expect(nextState.topProducts.filters.range).toBe('today');
  });
});
