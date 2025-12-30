/**
 * Sales Report Page
 * @file src/pages/reports/SalesReportPage.jsx
 * @description Report page showing sales revenue by day with chart and table visualization
 */

import React, { useState, useEffect, useMemo, useCallback } from 'react';
import { Select, DatePicker, Spin, Empty, App } from 'antd';
import { CloseOutlined, LeftOutlined, RightOutlined, CalendarOutlined, PlusOutlined, MinusOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import { Bar } from 'react-chartjs-2';
import {
    Chart as ChartJS,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip as ChartTooltip,
    Legend,
} from 'chart.js';
import reportApi from '../../api/reportApi';
import { formatCurrency } from '../../utils/formatters';
import styles from './SalesReportPage.module.css';

ChartJS.register(BarElement, CategoryScale, LinearScale, ChartTooltip, Legend);

// Sample chart data for fallback
const SAMPLE_CHART_DATA = {
    labels: Array.from({ length: 26 }, (_, idx) => String(idx + 1).padStart(2, '0')),
    values: [
        6_500_000, 0, 8_000_000, 0, 0, 1_500_000, 0, 0, 0, 0,
        1_800_000, 0, 0, 0, 2_200_000, 3_500_000, 0, 0, 4_800_000, 0,
        0, 5_200_000, 1_500_000, 3_800_000, 0, 15_000_000,
    ],
    branchLabel: 'Lano - HN',
};

// Sample table data for fallback
const SAMPLE_TABLE_DATA = [
    {
        date: '30/11/2025',
        revenue: 2_220_000,
        returns: 0,
        netRevenue: 2_220_000,
        invoices: [
            { id: 'HD031582', time: '30/11/2025 11:08', customer: 'Duy Phương', revenue: 900_000 },
            { id: 'HD031581', time: '30/11/2025 10:33', customer: 'Trần Thương', revenue: 1_320_000 },
        ],
    },
    {
        date: '28/11/2025',
        revenue: 14_480_000,
        returns: 0,
        netRevenue: 14_480_000,
        invoices: [],
    },
    {
        date: '26/11/2025',
        revenue: 2_150_000,
        returns: 0,
        netRevenue: 2_150_000,
        invoices: [],
    },
    {
        date: '25/11/2025',
        revenue: 5_100_000,
        returns: -4_000_000,
        netRevenue: 1_100_000,
        invoices: [],
    },
];

const SalesReportPage = () => {
    const { message } = App.useApp();
    const navigate = useNavigate();

    // Filter states
    const [viewType, setViewType] = useState('chart');
    const [interestType, setInterestType] = useState('time');
    const [selectedBranches, setSelectedBranches] = useState([]);
    const [priceList, setPriceList] = useState(null);
    const [timeRange, setTimeRange] = useState('thisMonth');
    const [customDates, setCustomDates] = useState([null, null]);
    const [salesMethod, setSalesMethod] = useState(null);
    const [salesChannel, setSalesChannel] = useState(null);

    // Data states
    const [branches, setBranches] = useState([]);
    const [priceLists, setPriceLists] = useState([]);
    const [salesChannels, setSalesChannels] = useState([]);
    const [chartData, setChartData] = useState(null);
    const [tableData, setTableData] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [expandedRows, setExpandedRows] = useState([]);

    // Fetch filter options on mount
    useEffect(() => {
        const fetchOptions = async () => {
            try {
                const [branchRes, priceListRes] = await Promise.all([
                    reportApi.getBranches(),
                    reportApi.getPriceLists().catch(() => ({ data: [] })),
                ]);

                if (branchRes.success && branchRes.data) {
                    setBranches(branchRes.data);
                    if (branchRes.data.length > 0) {
                        setSelectedBranches([branchRes.data[0]]);
                    }
                }

                if (priceListRes.data) {
                    setPriceLists(priceListRes.data);
                }
            } catch (err) {
                console.error('Failed to fetch filter options:', err);
            }
        };
        fetchOptions();
    }, []);

    // Fetch report data
    const fetchReportData = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const params = {
                range: timeRange,
                branchId: selectedBranches.length > 0 ? selectedBranches[0].id : undefined,
                priceListId: priceList || undefined,
                salesMethod: salesMethod || undefined,
                salesChannel: salesChannel || undefined,
            };

            if (timeRange === 'custom' && customDates[0] && customDates[1]) {
                params.startDate = customDates[0].format('YYYY-MM-DD');
                params.endDate = customDates[1].format('YYYY-MM-DD');
            }

            // Fetch both chart and table data in parallel
            const [chartResponse, tableResponse] = await Promise.all([
                reportApi.getSalesReport(params),
                reportApi.getSalesReportTable(params),
            ]);

            // Handle chart data
            if (chartResponse.success && chartResponse.data) {
                setChartData(chartResponse.data);
            } else {
                setChartData(SAMPLE_CHART_DATA);
            }

            // Handle table data from backend
            if (tableResponse.success && tableResponse.data?.tableData) {
                const tData = tableResponse.data.tableData.map((row, idx) => ({
                    key: idx,
                    date: row.date,
                    revenue: row.revenue,
                    returns: row.returns,
                    netRevenue: row.netRevenue,
                    invoices: row.invoices || [],
                }));
                setTableData(tData);
            } else {
                setTableData(SAMPLE_TABLE_DATA.map((row, idx) => ({ ...row, key: idx })));
                setError('Không thể tải dữ liệu bảng, hiển thị dữ liệu mẫu');
            }
        } catch (err) {
            console.error('Failed to fetch sales report:', err);
            setChartData(SAMPLE_CHART_DATA);
            setTableData(SAMPLE_TABLE_DATA.map((row, idx) => ({ ...row, key: idx })));
            setError('Lỗi kết nối, hiển thị dữ liệu mẫu');
        } finally {
            setLoading(false);
        }
    }, [timeRange, selectedBranches, priceList, salesMethod, salesChannel, customDates]);

    useEffect(() => {
        fetchReportData();
    }, [fetchReportData]);

    const toggleRow = (key) => {
        setExpandedRows(prev =>
            prev.includes(key) ? prev.filter(k => k !== key) : [...prev, key]
        );
    };

    const handleInvoiceClick = (invoiceId) => {
        navigate(`/invoices?code=${invoiceId}`);
    };

    const removeBranch = (branchId) => {
        setSelectedBranches(prev => prev.filter(b => b.id !== branchId));
    };

    const addBranch = (branchId) => {
        const branch = branches.find(b => b.id === branchId);
        if (branch && !selectedBranches.find(b => b.id === branchId)) {
            setSelectedBranches(prev => [...prev, branch]);
        }
    };

    const navigateMonth = (direction) => {
        setTimeRange(direction === 'prev' ? 'lastMonth' : 'thisMonth');
    };

    const chartTitle = useMemo(() => {
        switch (timeRange) {
            case 'thisMonth': return 'Doanh thu thuần tháng này';
            case 'lastMonth': return 'Doanh thu thuần tháng trước';
            case 'custom':
                if (customDates[0] && customDates[1]) {
                    return `Doanh thu thuần ${customDates[0].format('DD/MM')} - ${customDates[1].format('DD/MM/YYYY')}`;
                }
                return 'Doanh thu thuần';
            default: return 'Doanh thu thuần';
        }
    }, [timeRange, customDates]);

    const tableTotals = useMemo(() => {
        return tableData.reduce((acc, row) => ({
            revenue: acc.revenue + (row.revenue || 0),
            returns: acc.returns + (row.returns || 0),
            netRevenue: acc.netRevenue + (row.netRevenue || 0),
        }), { revenue: 0, returns: 0, netRevenue: 0 });
    }, [tableData]);

    const barChartData = useMemo(() => {
        const data = chartData || SAMPLE_CHART_DATA;
        return {
            labels: data.labels || [],
            datasets: [{
                label: data.branchLabel || 'Doanh thu',
                data: data.values || [],
                backgroundColor: '#1890ff',
                borderRadius: 4,
                hoverBackgroundColor: '#40a9ff',
            }],
        };
    }, [chartData]);

    const chartOptions = useMemo(() => ({
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'rectRounded', padding: 20, color: '#666' } },
            tooltip: { callbacks: { label: (ctx) => `${formatCurrency(ctx.parsed.y || 0)} đ` } },
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: '#888' } },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#888', callback: (value) => `${Math.round(value / 1_000_000)} tr` } },
        },
    }), []);

    // Render table view for "Báo cáo" mode
    const renderTableView = () => (
        <div className={styles.reportTable}>
            <div className={styles.tableHeader}>
                <div className={styles.tableHeaderCell} style={{ width: '25%' }}>Thời gian</div>
                <div className={styles.tableHeaderCell} style={{ width: '25%', textAlign: 'right' }}>Doanh thu</div>
                <div className={styles.tableHeaderCell} style={{ width: '25%', textAlign: 'right' }}>Giá trị trả</div>
                <div className={styles.tableHeaderCell} style={{ width: '25%', textAlign: 'right' }}>Doanh thu thuần</div>
            </div>

            <div className={styles.tableTotalsRow}>
                <div className={styles.tableCell} style={{ width: '25%' }}></div>
                <div className={styles.tableCell} style={{ width: '25%', textAlign: 'right' }}>{formatCurrency(tableTotals.revenue)}</div>
                <div className={styles.tableCell} style={{ width: '25%', textAlign: 'right', color: tableTotals.returns < 0 ? '#ff4d4f' : undefined }}>
                    {tableTotals.returns !== 0 ? formatCurrency(tableTotals.returns) : '0'}
                </div>
                <div className={styles.tableCell} style={{ width: '25%', textAlign: 'right', color: '#52c41a', fontWeight: 600 }}>
                    {formatCurrency(tableTotals.netRevenue)}
                </div>
            </div>

            {tableData.map((row) => (
                <React.Fragment key={row.key}>
                    <div className={styles.tableRow} onClick={() => row.invoices?.length > 0 && toggleRow(row.key)}>
                        <div className={styles.tableCell} style={{ width: '25%' }}>
                            {row.invoices?.length > 0 && (
                                <span className={styles.expandIcon}>
                                    {expandedRows.includes(row.key) ? <MinusOutlined /> : <PlusOutlined />}
                                </span>
                            )}
                            {row.date}
                        </div>
                        <div className={styles.tableCell} style={{ width: '25%', textAlign: 'right' }}>{formatCurrency(row.revenue)}</div>
                        <div className={styles.tableCell} style={{ width: '25%', textAlign: 'right', color: row.returns < 0 ? '#ff4d4f' : undefined }}>
                            {row.returns !== 0 ? formatCurrency(row.returns) : '0'}
                        </div>
                        <div className={styles.tableCell} style={{ width: '25%', textAlign: 'right' }}>{formatCurrency(row.netRevenue)}</div>
                    </div>

                    {expandedRows.includes(row.key) && row.invoices?.length > 0 && (
                        <div className={styles.invoicesContainer}>
                            <div className={styles.invoiceHeader}>
                                <div className={styles.invoiceHeaderCell} style={{ width: '25%' }}>Mã hóa đơn</div>
                                <div className={styles.invoiceHeaderCell} style={{ width: '30%' }}>Thời gian</div>
                                <div className={styles.invoiceHeaderCell} style={{ width: '25%' }}>Khách hàng</div>
                                <div className={styles.invoiceHeaderCell} style={{ width: '20%', textAlign: 'right' }}>Doanh thu</div>
                            </div>
                            {row.invoices.map((invoice, idx) => (
                                <div key={idx} className={styles.invoiceRow}>
                                    <div className={styles.invoiceCell} style={{ width: '25%' }}>
                                        <a className={styles.invoiceLink} onClick={(e) => { e.stopPropagation(); handleInvoiceClick(invoice.id); }}>
                                            {invoice.id}
                                        </a>
                                    </div>
                                    <div className={styles.invoiceCell} style={{ width: '30%' }}>{invoice.time}</div>
                                    <div className={styles.invoiceCell} style={{ width: '25%' }}>{invoice.customer}</div>
                                    <div className={styles.invoiceCell} style={{ width: '20%', textAlign: 'right' }}>{formatCurrency(invoice.revenue)}</div>
                                </div>
                            ))}
                        </div>
                    )}
                </React.Fragment>
            ))}
        </div>
    );

    return (
        <div className={styles.pageContainer}>
            {/* Left Sidebar Filters */}
            <div className={styles.filterPanel}>
                <h1 className={styles.pageTitle}>Báo cáo bán hàng</h1>

                <div className={styles.filterSection}>
                    <span className={styles.filterLabel}>Kiểu hiển thị</span>
                    <div className={styles.viewToggle}>
                        <button className={`${styles.viewBtn} ${viewType === 'chart' ? styles.active : ''}`} onClick={() => setViewType('chart')}>Biểu đồ</button>
                        <button className={`${styles.viewBtn} ${viewType === 'report' ? styles.active : ''}`} onClick={() => setViewType('report')}>Báo cáo</button>
                    </div>
                </div>

                <div className={styles.filterSection}>
                    <span className={styles.filterLabel}>Mối quan tâm</span>
                    <Select className={styles.filterSelect} value={interestType} onChange={setInterestType}
                        options={[{ value: 'time', label: 'Thời gian' }, { value: 'product', label: 'Hàng hóa' }, { value: 'customer', label: 'Khách hàng' }, { value: 'employee', label: 'Nhân viên' }]} />
                </div>

                <div className={styles.filterSection}>
                    <span className={styles.filterLabel}>Chi nhánh</span>
                    <div className={styles.branchTags}>
                        {selectedBranches.map(branch => (
                            <span key={branch.id} className={styles.branchTag}>
                                {branch.name}
                                <CloseOutlined className={styles.removeBtn} onClick={() => removeBranch(branch.id)} />
                            </span>
                        ))}
                    </div>
                    <Select className={styles.filterSelect} placeholder="Chọn chi nhánh" value={null} onChange={addBranch}
                        options={branches.filter(b => !selectedBranches.find(s => s.id === b.id)).map(b => ({ value: b.id, label: b.name }))} style={{ marginTop: 8 }} allowClear />
                </div>

                <div className={styles.filterSection}>
                    <span className={styles.filterLabel}>Bảng giá</span>
                    <Select className={styles.filterSelect} placeholder="Chọn bảng giá" value={priceList} onChange={setPriceList} allowClear
                        options={priceLists.map(p => ({ value: p.id, label: p.name }))} />
                </div>

                <div className={styles.filterSection}>
                    <span className={styles.filterLabel}>Thời gian</span>
                    <div className={styles.timeOptions}>
                        <label className={`${styles.timeOption} ${timeRange === 'thisMonth' ? styles.active : ''}`}>
                            <input type="radio" name="timeRange" checked={timeRange === 'thisMonth'} onChange={() => setTimeRange('thisMonth')} />
                            Tháng này
                            <LeftOutlined style={{ marginLeft: 'auto', cursor: 'pointer' }} onClick={() => navigateMonth('prev')} />
                            <RightOutlined style={{ cursor: 'pointer' }} onClick={() => navigateMonth('next')} />
                        </label>
                        <label className={`${styles.timeOption} ${timeRange === 'lastMonth' ? styles.active : ''}`}>
                            <input type="radio" name="timeRange" checked={timeRange === 'lastMonth'} onChange={() => setTimeRange('lastMonth')} />
                            Tháng trước
                        </label>
                        <label className={`${styles.timeOption} ${timeRange === 'custom' ? styles.active : ''}`}>
                            <input type="radio" name="timeRange" checked={timeRange === 'custom'} onChange={() => setTimeRange('custom')} />
                            Tùy chỉnh
                            <CalendarOutlined style={{ marginLeft: 'auto' }} />
                        </label>
                    </div>
                    {timeRange === 'custom' && (
                        <DatePicker.RangePicker className={styles.customDatePicker} value={customDates} onChange={setCustomDates} format="DD/MM/YYYY" />
                    )}
                </div>

                <div className={styles.filterSection}>
                    <span className={styles.filterLabel}>Phương thức bán hàng</span>
                    <Select className={styles.filterSelect} placeholder="Chọn phương thức bán hàng" value={salesMethod} onChange={setSalesMethod} allowClear
                        options={[{ value: 'pos', label: 'POS' }, { value: 'delivery', label: 'Giao hàng' }, { value: 'online', label: 'Online' }]} />
                </div>

                <div className={styles.filterSection}>
                    <span className={styles.filterLabel}>Kênh bán</span>
                    <Select className={styles.filterSelect} placeholder="Chọn kênh bán" value={salesChannel} onChange={setSalesChannel} allowClear
                        options={salesChannels.map(c => ({ value: c.id, label: c.name }))} />
                </div>
            </div>

            {/* Main Content Area */}
            <div className={styles.mainContent}>
                <div className={styles.chartCard}>
                    <div className={styles.chartHeader}>
                        <h2 className={styles.chartTitle}>{chartTitle}</h2>
                    </div>

                    {loading ? (
                        <div className={styles.loading}><Spin size="large" /></div>
                    ) : viewType === 'chart' ? (
                        <div className={styles.chartWrapper}>
                            {barChartData.labels?.length > 0 ? <Bar data={barChartData} options={chartOptions} /> : <div className={styles.emptyState}><Empty description="Chưa có dữ liệu" /></div>}
                        </div>
                    ) : (
                        renderTableView()
                    )}

                    {error && <p style={{ color: '#faad14', fontSize: 12, marginTop: 8 }}>{error}</p>}
                </div>
            </div>
        </div>
    );
};

export default SalesReportPage;
