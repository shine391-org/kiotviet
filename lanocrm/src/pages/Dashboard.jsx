/**
 * Dashboard overview page (analytics)
 * @agent-layer: ui
 * @agent-pattern: Responsive dashboard layout
 * @agent-reusable: MEDIUM
 */

import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  BarChartOutlined,
  ColumnHeightOutlined,
  ArrowDownOutlined,
  ArrowUpOutlined,
  ReloadOutlined,
} from '@ant-design/icons';
import { Bar } from 'react-chartjs-2';
import {
  Card,
  Select,
  Segmented,
  Skeleton,
  Tag,
  Tooltip,
} from 'antd';
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip as ChartTooltip,
  Legend,
} from 'chart.js';
import {
  fetchActivities,
  fetchKpiToday,
  fetchRevenueChart,
  fetchTopCustomers,
  fetchTopProducts,
  setActivitiesLimit,
  setRevenueFilters,
  setTopCustomersFilters,
  setTopProductsFilters,
} from '../store/slices/dashboardSlice';
import {
  formatCurrency,
  getRelativeTime,
} from '../utils/formatters';
import '../styles/dashboard.css';

ChartJS.register(BarElement, CategoryScale, LinearScale, ChartTooltip, Legend);

const SAMPLE_KPI = {
  revenue: 68190000,
  returns: 0,
  netRevenue: 66190000,
  netChange: -47.78,
  comparisonLabel: 'so với cùng kỳ tháng trước',
};

const SAMPLE_REVENUE_CHART = {
  labels: Array.from({ length: 26 }, (_, idx) => String(idx + 1).padStart(2, '0')),
  values: [
    0, 0, 0, 0, 0, 12_000_000, 0, 6_200_000, 0, 0, 0, 0, 0,
    8_500_000, 0, 0, 0, 6_800_000, 0, 0, 4_200_000, 0, 0, 0, 0, 0,
  ],
  branchLabel: 'Lano - HN',
  total: 66_190_000,
};

const SAMPLE_TOP_PRODUCTS = [
  { id: 1, name: 'Túi Clutch da sáp ong mạ khóa handmade khâu tay thủ công Lano TLHD...', value: 8_000_000 },
  { id: 2, name: 'Cặp nam công sở da bò cao cấp CD013', value: 4_700_000 },
  { id: 3, name: 'Túi gài theo chiều da sáp ong khâu tay thủ công 100% Lano TLHD...', value: 3_000_000 },
  { id: 4, name: 'Cặp sách nam da bò có gài mạ đồng Lano VCTK05 - D', value: 2_100_000 },
  { id: 5, name: 'Ví dài nam da bò handmade dập logo', value: 2_000_000 },
  { id: 6, name: 'Dây lưng da bò handmade da sáp', value: 1_900_000 },
  { id: 7, name: 'Ví da nam cao cấp màu đen Lano CN-01', value: 1_600_000 },
  { id: 8, name: 'Cặp nam da bò cao cấp mã mới 2020 - Dan', value: 1_500_000 },
  { id: 9, name: 'Cặp nam đeo chéo cao cấp da bò CD013 - NS', value: 900_000 },
  { id: 10, name: 'Cặp sách nam công sở da bò đen khóa kéo Lano TLHD7', value: 800_000 },
];

const SAMPLE_TOP_CUSTOMERS = [
  { id: 1, name: 'Thái Anh', value: 8_000_000 },
  { id: 2, name: 'chu Hiếu', value: 4_500_000 },
  { id: 3, name: 'A.Triều', value: 4_200_000 },
  { id: 4, name: 'Anh Vũ', value: 4_000_000 },
  { id: 5, name: 'C trang', value: 3_700_000 },
  { id: 6, name: 'Lê Thanh Hà', value: 3_000_000 },
  { id: 7, name: 'A Trí', value: 2_900_000 },
  { id: 8, name: 'A Mạnh', value: 2_700_000 },
  { id: 9, name: 'Nguyễn Đình Long', value: 2_400_000 },
  { id: 10, name: 'a Hoàng', value: 2_100_000 },
];

const SAMPLE_ACTIVITIES = [
  {
    id: 'act-1',
    type: 'delivery',
    username: 'nhung',
    action: 'Giao hàng',
    amount: 2_150_000,
    timeAgo: '18 giờ trước',
    relatedCode: 'GYNTHBCE',
    linkedPage: '#/invoices?code=GYNTHBCE',
  },
  {
    id: 'act-2',
    type: 'return',
    username: 'nhung',
    action: 'Nhận trả hàng',
    amount: 1_800_000,
    timeAgo: '2 ngày trước',
    relatedCode: 'GYNT8435',
    linkedPage: '#/returns?code=GYNT8435',
  },
  {
    id: 'act-3',
    type: 'purchase',
    username: 'Chị Phương Anh',
    action: 'Nhập hàng',
    amount: 1_050_000,
    timeAgo: '2 ngày trước',
    relatedCode: 'PO-1050',
    linkedPage: '#/purchase-orders/po-1050',
  },
  {
    id: 'act-4',
    type: 'invoice',
    username: 'nhung',
    action: 'Bán đơn hàng',
    amount: 1_800_000,
    timeAgo: '2 ngày trước',
    relatedCode: 'INV-1800',
    linkedPage: '#/invoices?code=INV-1800',
  },
  {
    id: 'act-5',
    type: 'delivery',
    username: 'nhung',
    action: 'Giao hàng',
    amount: 1_800_000,
    timeAgo: '2 ngày trước',
    relatedCode: 'GYNT783X',
    linkedPage: '#/deliveries/GYNT783X',
  },
  {
    id: 'act-6',
    type: 'purchase',
    username: 'Chị Phương Anh',
    action: 'Nhập hàng',
    amount: 1_050_000,
    timeAgo: '2 ngày trước',
    relatedCode: 'PO-21',
    linkedPage: '#/purchase-orders/PO-21',
  },
  {
    id: 'act-7',
    type: 'return',
    username: 'Chị Phương Anh',
    action: 'Nhận trả hàng',
    amount: 1_050_000,
    timeAgo: '2 ngày trước',
    relatedCode: 'RT-1050',
    linkedPage: '#/returns/RT-1050',
  },
  {
    id: 'act-8',
    type: 'invoice',
    username: 'nhung',
    action: 'Bán đơn hàng',
    amount: 800_000,
    timeAgo: '2 ngày trước',
    relatedCode: 'INV-800',
    linkedPage: '#/invoices/INV-800',
  },
  {
    id: 'act-9',
    type: 'delivery',
    username: 'nhung',
    action: 'Giao hàng nhanh',
    amount: 1_800_000,
    timeAgo: '3 ngày trước',
    relatedCode: 'GYNTDEEP',
    linkedPage: '#/deliveries/GYNTDEEP',
  },
  {
    id: 'act-10',
    type: 'invoice',
    username: 'nhung',
    action: 'Bán đơn hàng',
    amount: 1_500_000,
    timeAgo: '3 ngày trước',
    relatedCode: 'INV-1500',
    linkedPage: '#/invoices/INV-1500',
  },
];

const activityTypeMeta = {
  invoice: { icon: '🧾', color: '#1890ff', label: 'Bán đơn hàng' },
  purchase: { icon: '📦', color: '#52c41a', label: 'Nhập hàng' },
  return: { icon: '↩️', color: '#fa8c16', label: 'Nhận trả hàng' },
  delivery: { icon: '🚚', color: '#722ed1', label: 'Vận đơn giao hàng' },
};

const productMetricOptions = [
  { value: 'net_revenue', label: 'Theo doanh thu thuần' },
  { value: 'revenue', label: 'Theo doanh thu' },
  { value: 'quantity', label: 'Theo số lượng' },
  { value: 'profit_margin', label: 'Theo lợi nhuận' },
];

const rangeOptions = [
  { value: 'today', label: 'Hôm nay' },
  { value: 'week', label: 'Tuần này' },
  { value: 'month', label: 'Tháng này' },
  { value: 'custom', label: 'Tùy chỉnh' },
];

const breakdownOptions = [
  { label: 'Theo ngày', value: 'day' },
  { label: 'Theo giờ', value: 'hour' },
  { label: 'Theo thứ', value: 'weekday' },
];

const useBreakpoints = () => {
  const [width, setWidth] = useState(
    typeof window !== 'undefined' ? window.innerWidth : 1440
  );

  useEffect(() => {
    const handleResize = () => setWidth(window.innerWidth);
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  return {
    isDesktop: width > 1024,
    isTablet: width <= 1024 && width >= 768,
    isMobile: width < 768,
  };
};

const formatMillions = (value) => {
  if (value === null || value === undefined) return '0';
  if (Math.abs(value) >= 1_000_000) {
    const million = value / 1_000_000;
    const precision = Math.abs(million) >= 10 ? 0 : 1;
    return `${million.toFixed(precision)} tr`;
  }
  return formatCurrency(value);
};

const ensureArray = (value) => {
  if (Array.isArray(value)) return value;
  if (Array.isArray(value?.items)) return value.items;
  if (value && typeof value === 'object') return Object.values(value);
  return [];
};

const percentageText = (value) => {
  const safeValue = Number.isFinite(value) ? value : 0;
  const prefix = safeValue > 0 ? '+' : '';
  return `${prefix}${safeValue.toFixed(2)}%`;
};

const Dashboard = () => {
  const dispatch = useDispatch();
  const { user } = useSelector((state) => state.auth);
  const {
    kpi,
    revenue,
    topProducts,
    topCustomers,
    activities,
    lastUpdated,
  } = useSelector((state) => state.dashboard);

  const { isMobile } = useBreakpoints();

  useEffect(() => {
    dispatch(fetchKpiToday());
    dispatch(fetchRevenueChart());
    dispatch(fetchTopProducts());
    dispatch(fetchTopCustomers());
    dispatch(fetchActivities({ limit: activities.limit }));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [dispatch]);

  const isRevenueFallback = !revenue.loading && Boolean(revenue.error);
  const isProductFallback = !topProducts.loading && Boolean(topProducts.error);
  const isCustomerFallback = !topCustomers.loading && Boolean(topCustomers.error);
  const isActivityFallback = !activities.loading && Boolean(activities.error);

  const revenueData = useMemo(() => {
    if (isRevenueFallback) return SAMPLE_REVENUE_CHART;
    return revenue.data;
  }, [isRevenueFallback, revenue.data]);

  const productItems = useMemo(() => {
    if (isProductFallback) return SAMPLE_TOP_PRODUCTS;
    return ensureArray(topProducts.items);
  }, [isProductFallback, topProducts.items]);

  const customerItems = useMemo(() => {
    if (isCustomerFallback) return SAMPLE_TOP_CUSTOMERS;
    return ensureArray(topCustomers.items);
  }, [isCustomerFallback, topCustomers.items]);

  const activityItems = useMemo(() => {
    const source = isActivityFallback ? SAMPLE_ACTIVITIES : ensureArray(activities.items);
    const limit = isMobile ? Math.min(activities.limit, 8) : activities.limit;
    return (source || []).slice(0, limit);
  }, [activities.items, activities.limit, isActivityFallback, isMobile]);

  const chartData = useMemo(
    () => ({
      labels: revenueData.labels || [],
      datasets: [
        {
          label: revenueData.branchLabel || 'Lano - HN',
          data: revenueData.values || [],
          backgroundColor: '#1890ff',
          borderRadius: 6,
          hoverBackgroundColor: '#0d6efd',
          barThickness: isMobile ? 16 : undefined,
        },
      ],
    }),
    [isMobile, revenueData]
  );

  const chartOptions = useMemo(
    () => ({
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: revenue.filters.chartType === 'bar' ? 'y' : 'x',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: '#4b5563',
          },
        },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const value =
                revenue.filters.chartType === 'bar'
                  ? ctx.parsed.x
                  : ctx.parsed.y;
              return `${formatCurrency(value || 0)} đ`;
            },
          },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            color: '#6b7280',
            maxRotation: isMobile ? 45 : 0,
            minRotation: isMobile ? 45 : 0,
          },
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,0.04)' },
          ticks: {
            color: '#6b7280',
            callback: (value) => `${Math.round(value / 1_000_000)} tr`,
          },
        },
      },
    }),
    [isMobile, revenue.filters.chartType]
  );

  const refreshAll = () => {
    dispatch(fetchKpiToday());
    dispatch(fetchRevenueChart(revenue.filters));
    dispatch(fetchTopProducts(topProducts.filters));
    dispatch(fetchTopCustomers(topCustomers.filters));
    dispatch(fetchActivities({ limit: activities.limit }));
  };

  const handleRangeChange = (value) => {
    const nextFilters = { ...revenue.filters, range: value };
    dispatch(setRevenueFilters(nextFilters));
    dispatch(fetchRevenueChart(nextFilters));
  };

  const handleBreakdownChange = (value) => {
    const nextFilters = { ...revenue.filters, period: value };
    dispatch(setRevenueFilters(nextFilters));
    dispatch(fetchRevenueChart(nextFilters));
  };

  const handleChartTypeChange = (type) => {
    const nextFilters = { ...revenue.filters, chartType: type };
    dispatch(setRevenueFilters(nextFilters));
    dispatch(fetchRevenueChart(nextFilters));
  };

  const handleProductMetricChange = (value) => {
    const nextFilters = { ...topProducts.filters, metric: value };
    dispatch(setTopProductsFilters(nextFilters));
    dispatch(fetchTopProducts(nextFilters));
  };

  const handleProductRangeChange = (value) => {
    const nextFilters = { ...topProducts.filters, range: value };
    dispatch(setTopProductsFilters(nextFilters));
    dispatch(fetchTopProducts(nextFilters));
  };

  const handleCustomerRangeChange = (value) => {
    const nextFilters = { ...topCustomers.filters, range: value };
    dispatch(setTopCustomersFilters(nextFilters));
    dispatch(fetchTopCustomers(nextFilters));
  };

  const handleActivityLimitChange = (value) => {
    dispatch(setActivitiesLimit(value));
    dispatch(fetchActivities({ limit: value }));
  };

  const lastUpdatedText = lastUpdated
    ? getRelativeTime(lastUpdated)
    : 'Vừa cập nhật';

  const netChangePositive = (kpi.data?.netChange || 0) > 0;
  const netChangeValue =
    kpi.error && !kpi.loading ? SAMPLE_KPI.netChange : kpi.data?.netChange || 0;

  const kpiData = kpi.error && !kpi.loading ? SAMPLE_KPI : kpi.data;

  return (
    <div className="dashboard-page">
      <div className="dashboard-header">
        <div>
          <p className="eyebrow">Tổng quan</p>
          <h1 className="page-title">
            Xin chào, {user?.username || 'User'}!
          </h1>
          <p className="page-subtitle">
            Ảnh chụp nhanh hiệu suất bán hàng hôm nay
          </p>
        </div>
        <div className="header-actions">
          <span className="live-dot" />
          <span className="updated-at">Cập nhật {lastUpdatedText}</span>
          <button className="ghost-btn" onClick={refreshAll} aria-label="Làm mới dữ liệu">
            <ReloadOutlined />
            <span>Làm mới</span>
          </button>
        </div>
      </div>

      <div className="dashboard-grid">
        <div className="dashboard-main">
          <Card
            className="kpi-card-wrapper"
            title="Kết quả bán hàng hôm nay"
            variant="borderless"
          >
            {kpi.loading ? (
              <div className="kpi-grid">
                {[1, 2, 3].map((item) => (
                  <Skeleton.Button
                    key={item}
                    active
                    shape="round"
                    className="kpi-skeleton"
                  />
                ))}
              </div>
            ) : (
              <div className="kpi-grid">
                <KpiCard
                  icon="💰"
                  title="Doanh thu"
                  value={`${formatCurrency(kpiData.revenue)} đ`}
                  color="#1890ff"
                />
                <KpiCard
                  icon="📦"
                  title="Trả hàng"
                  value={`${formatCurrency(kpiData.returns)} đ`}
                  color="#fa8c16"
                />
                <KpiCard
                  icon="📉"
                  title="Doanh thu thuần"
                  value={`${formatCurrency(kpiData.netRevenue)} đ`}
                  color={netChangePositive ? '#52c41a' : '#ff4d4f'}
                  delta={netChangeValue}
                  description={kpiData.comparisonLabel}
                />
              </div>
            )}
            {kpi.error && (
              <p className="error-hint">Không thể tải KPI, hiển thị dữ liệu mẫu.</p>
            )}
          </Card>

          <Card
            className="chart-card"
            title="Doanh thu thuần"
            variant="borderless"
            extra={
              <div className="chart-total">
                <span className="total-label">Tổng</span>
                <span className="total-value">
                  {formatCurrency(revenueData.total || 0)}
                </span>
                {isRevenueFallback && <Tag color="orange">Dữ liệu mẫu</Tag>}
              </div>
            }
          >
            <div className="chart-toolbar">
              <div className="chart-toggle">
                <Tooltip title="Cột dọc">
                  <button
                    className={`icon-toggle ${revenue.filters.chartType === 'column' ? 'active' : ''}`}
                    onClick={() => handleChartTypeChange('column')}
                    aria-label="Cột dọc"
                  >
                    <ColumnHeightOutlined />
                  </button>
                </Tooltip>
                <Tooltip title="Thanh ngang">
                  <button
                    className={`icon-toggle ${revenue.filters.chartType === 'bar' ? 'active' : ''}`}
                    onClick={() => handleChartTypeChange('bar')}
                    aria-label="Thanh ngang"
                  >
                    <BarChartOutlined />
                  </button>
                </Tooltip>
              </div>
              <Select
                size="middle"
                className="range-select"
                options={rangeOptions}
                value={revenue.filters.range}
                onChange={handleRangeChange}
              />
              <Segmented
                size="middle"
                options={breakdownOptions}
                value={revenue.filters.period}
                onChange={handleBreakdownChange}
              />
            </div>

            <div className="chart-body">
              {revenue.loading ? (
                <Skeleton.Input active block style={{ height: 320 }} />
              ) : revenueData.labels?.length ? (
                <div className="chart-wrapper" role="img" aria-label="Biểu đồ doanh thu">
                  <Bar data={chartData} options={chartOptions} />
                </div>
              ) : (
                <div className="empty-state">Chưa có dữ liệu biểu đồ</div>
              )}
              {revenue.error && (
                <p className="error-hint">Không thể tải biểu đồ, hiển thị dữ liệu mẫu.</p>
              )}
            </div>
          </Card>

          <div className="split-row">
            <Card
              className="list-card"
              variant="borderless"
              title="Top 10 hàng bán chạy"
              extra={
                <div className="list-filters">
                  <Select
                    size="small"
                    value={topProducts.filters.metric}
                    onChange={handleProductMetricChange}
                    options={productMetricOptions}
                  />
                  <Select
                    size="small"
                    value={topProducts.filters.range}
                    onChange={handleProductRangeChange}
                    options={rangeOptions}
                  />
                </div>
              }
            >
              {topProducts.loading ? (
                <Skeleton active paragraph={{ rows: 6 }} />
              ) : productItems.length ? (
                <RankingList items={productItems} fallback={isProductFallback} />
              ) : (
                <div className="empty-state">Chưa có dữ liệu</div>
              )}
              {topProducts.error && (
                <p className="error-hint">Hiển thị dữ liệu mẫu do lỗi tải.</p>
              )}
            </Card>

            <Card
              className="list-card"
              variant="borderless"
              title="Top 10 khách mua nhiều nhất"
              extra={
                <Select
                  size="small"
                  value={topCustomers.filters.range}
                  onChange={handleCustomerRangeChange}
                  options={rangeOptions}
                />
              }
            >
              {topCustomers.loading ? (
                <Skeleton active paragraph={{ rows: 6 }} />
              ) : customerItems.length ? (
                <RankingList
                  items={customerItems}
                  accent="customer"
                  fallback={isCustomerFallback}
                />
              ) : (
                <div className="empty-state">Chưa có dữ liệu</div>
              )}
              {topCustomers.error && (
                <p className="error-hint">Hiển thị dữ liệu mẫu do lỗi tải.</p>
              )}
            </Card>
          </div>
        </div>

        <Card
          className="activity-card"
          title="Hoạt động gần đây"
          variant="borderless"
          extra={
            <Select
              size="small"
              value={activities.limit}
              onChange={handleActivityLimitChange}
              options={[
                { label: '10 mục', value: 10 },
                { label: '12 mục', value: 12 },
                { label: '15 mục', value: 15 },
              ]}
            />
          }
        >
          {activities.loading ? (
            <Skeleton active paragraph={{ rows: 8 }} />
          ) : activityItems.length ? (
            <ul className="activity-list">
              {activityItems.map((item) => {
                const meta = activityTypeMeta[item.type] || activityTypeMeta.invoice;
                const timeText = item.timeAgo || getRelativeTime(item.timestamp) || '';
                return (
                  <li key={item.id} className="activity-item">
                    <div
                      className="activity-icon"
                      style={{ backgroundColor: `${meta.color}10`, color: meta.color }}
                    >
                      {meta.icon}
                    </div>
                    <div className="activity-main">
                      <div className="activity-top">
                        <div className="activity-user">{item.username}</div>
                        <div className="activity-action">
                          {item.action || meta.label}
                        </div>
                      </div>
                      <div className="activity-bottom">
                        <a className="activity-amount" href={item.linkedPage || '#'}>{formatCurrency(item.amount || 0)} đ</a>
                        <span className="activity-time">{timeText}</span>
                      </div>
                    </div>
                  </li>
                );
              })}
            </ul>
          ) : (
            <div className="empty-state">Chưa có hoạt động</div>
          )}
          {activities.error && (
            <p className="error-hint">Hiển thị dữ liệu mẫu do lỗi tải.</p>
          )}
        </Card>
      </div>
    </div>
  );
};

const KpiCard = ({ icon, title, value, color, delta, description }) => (
  <div className="kpi-card">
    <div className="kpi-icon" style={{ color }}>
      {icon}
    </div>
    <div className="kpi-content">
      <p className="kpi-title">{title}</p>
      <p className="kpi-value">{value}</p>
      {delta !== undefined && (
        <div className="kpi-delta">
          {delta >= 0 ? (
            <ArrowUpOutlined style={{ color: '#52c41a' }} />
          ) : (
            <ArrowDownOutlined style={{ color: '#ff4d4f' }} />
          )}
          <span className={delta >= 0 ? 'delta-positive' : 'delta-negative'}>
            {percentageText(delta)}
          </span>
          <span className="kpi-note">{description}</span>
        </div>
      )}
    </div>
  </div>
);

const RankingList = ({ items, accent = 'product', fallback = false }) => {
  const maxValue = Math.max(...items.map((i) => i.value), 1);
  const gradient =
    accent === 'customer'
      ? 'linear-gradient(90deg, #13c2c2 0%, #36cfc9 100%)'
      : 'linear-gradient(90deg, #1890ff 0%, #40a9ff 100%)';

  return (
    <div className="ranking-list">
      {fallback && <Tag color="orange">Dữ liệu mẫu</Tag>}
      {items.map((item, idx) => {
        const width = Math.min(100, (item.value / maxValue) * 100);
        return (
          <div key={item.id || idx} className="ranking-row">
            <div className="ranking-name" title={item.name}>
              <span className="rank-number">{idx + 1}.</span> {item.name}
            </div>
            <div className="ranking-bar">
              <div
                className="ranking-bar-fill"
                style={{ width: `${width}%`, background: gradient }}
              />
              <span className="ranking-value">{formatMillions(item.value)}</span>
            </div>
          </div>
        );
      })}
    </div>
  );
};

export default Dashboard;
