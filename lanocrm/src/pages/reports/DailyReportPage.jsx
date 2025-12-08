import React, { useState, useEffect, useCallback } from 'react';
import { Card, DatePicker, Select, Row, Col, Statistic, Table, Spin, Empty, App } from 'antd';
import {
    ShoppingCartOutlined,
    DollarOutlined,
    RollbackOutlined,
    WalletOutlined,
    TrophyOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import posApi from '../../api/posApi';
import styles from './DailyReportPage.module.css';

const DailyReportPage = () => {
    const { message } = App.useApp();
    const [loading, setLoading] = useState(false);
    const [selectedDate, setSelectedDate] = useState(dayjs());
    const [selectedBranch, setSelectedBranch] = useState('');
    const [branches, setBranches] = useState([]);
    const [reportData, setReportData] = useState(null);

    // Fetch branches on mount
    useEffect(() => {
        const fetchBranches = async () => {
            try {
                const response = await posApi.getBranches();
                if (response.success && response.data) {
                    setBranches(response.data.map(b => ({
                        value: b.id,
                        label: b.name,
                    })));
                }
            } catch (error) {
                console.error('Failed to fetch branches:', error);
            }
        };
        fetchBranches();
    }, []);

    // Fetch report data
    const fetchReport = useCallback(async () => {
        setLoading(true);
        try {
            const params = {
                date: selectedDate.format('YYYY-MM-DD'),
                branch_id: selectedBranch || undefined,
            };
            const response = await posApi.getDailyReport(params);
            if (response.success) {
                setReportData(response.data);
            } else {
                message.error(response.message || 'Không thể tải báo cáo');
            }
        } catch (error) {
            console.error('Failed to fetch daily report:', error);
            message.error('Lỗi khi tải báo cáo cuối ngày');
        } finally {
            setLoading(false);
        }
    }, [selectedDate, selectedBranch, message]);

    useEffect(() => {
        fetchReport();
    }, [fetchReport]);

    const formatCurrency = (value) => {
        return (value || 0).toLocaleString('vi-VN');
    };

    // Top products columns
    const topProductsColumns = [
        {
            title: 'STT',
            dataIndex: 'index',
            key: 'index',
            width: 60,
            render: (_, __, index) => index + 1,
        },
        {
            title: 'Sản phẩm',
            dataIndex: 'name',
            key: 'name',
        },
        {
            title: 'Mã',
            dataIndex: 'code',
            key: 'code',
            width: 120,
        },
        {
            title: 'SL bán',
            dataIndex: 'quantity',
            key: 'quantity',
            width: 100,
            align: 'right',
        },
        {
            title: 'Doanh thu',
            dataIndex: 'revenue',
            key: 'revenue',
            width: 150,
            align: 'right',
            render: (value) => formatCurrency(value),
        },
    ];

    // Payment breakdown columns
    const paymentColumns = [
        {
            title: 'Phương thức',
            dataIndex: 'method',
            key: 'method',
        },
        {
            title: 'Số GD',
            dataIndex: 'count',
            key: 'count',
            width: 100,
            align: 'right',
        },
        {
            title: 'Số tiền',
            dataIndex: 'amount',
            key: 'amount',
            width: 150,
            align: 'right',
            render: (value) => formatCurrency(value),
        },
    ];

    return (
        <div className={styles.container}>
            <div className={styles.header}>
                <h1 className={styles.title}>Báo cáo cuối ngày</h1>
                <div className={styles.filters}>
                    <DatePicker
                        value={selectedDate}
                        onChange={(date) => setSelectedDate(date || dayjs())}
                        format="DD/MM/YYYY"
                        allowClear={false}
                        className={styles.datePicker}
                    />
                    <Select
                        placeholder="Tất cả chi nhánh"
                        value={selectedBranch}
                        onChange={setSelectedBranch}
                        options={[{ value: '', label: 'Tất cả chi nhánh' }, ...branches]}
                        className={styles.branchSelect}
                        allowClear
                    />
                </div>
            </div>

            {loading ? (
                <div className={styles.loading}>
                    <Spin size="large" />
                </div>
            ) : reportData ? (
                <>
                    {/* Sales Summary */}
                    <Row gutter={[16, 16]}>
                        <Col xs={24} sm={12} md={6}>
                            <Card className={styles.statCard}>
                                <Statistic
                                    title="Tổng đơn hàng"
                                    value={reportData.sales?.total_orders || 0}
                                    prefix={<ShoppingCartOutlined />}
                                    className={styles.statOrders}
                                />
                            </Card>
                        </Col>
                        <Col xs={24} sm={12} md={6}>
                            <Card className={styles.statCard}>
                                <Statistic
                                    title="Tổng sản phẩm"
                                    value={reportData.sales?.total_items || 0}
                                    prefix={<TrophyOutlined />}
                                    className={styles.statItems}
                                />
                            </Card>
                        </Col>
                        <Col xs={24} sm={12} md={6}>
                            <Card className={styles.statCard}>
                                <Statistic
                                    title="Doanh thu thuần"
                                    value={reportData.sales?.net_sales || 0}
                                    prefix={<DollarOutlined />}
                                    suffix="₫"
                                    formatter={(value) => formatCurrency(value)}
                                    className={styles.statRevenue}
                                />
                            </Card>
                        </Col>
                        <Col xs={24} sm={12} md={6}>
                            <Card className={styles.statCard}>
                                <Statistic
                                    title="Giá trị TB/đơn"
                                    value={reportData.sales?.average_order_value || 0}
                                    prefix={<WalletOutlined />}
                                    suffix="₫"
                                    formatter={(value) => formatCurrency(value)}
                                    className={styles.statAvg}
                                />
                            </Card>
                        </Col>
                    </Row>

                    {/* Returns & Discounts */}
                    <Row gutter={[16, 16]} className={styles.section}>
                        <Col xs={24} md={12}>
                            <Card title="Trả hàng" className={styles.infoCard}>
                                <Row gutter={16}>
                                    <Col span={8}>
                                        <Statistic
                                            title="Số đơn trả"
                                            value={reportData.returns?.total_returns || 0}
                                            prefix={<RollbackOutlined />}
                                        />
                                    </Col>
                                    <Col span={8}>
                                        <Statistic
                                            title="Tiền hoàn"
                                            value={reportData.returns?.refund_amount || 0}
                                            formatter={(value) => formatCurrency(value)}
                                            suffix="₫"
                                        />
                                    </Col>
                                    <Col span={8}>
                                        <Statistic
                                            title="Giảm giá"
                                            value={reportData.sales?.discounts || 0}
                                            formatter={(value) => formatCurrency(value)}
                                            suffix="₫"
                                        />
                                    </Col>
                                </Row>
                            </Card>
                        </Col>
                        <Col xs={24} md={12}>
                            <Card title="Quỹ tiền mặt" className={styles.infoCard}>
                                <Row gutter={16}>
                                    <Col span={6}>
                                        <Statistic
                                            title="Đầu ngày"
                                            value={reportData.cash_drawer?.opening_balance || 0}
                                            formatter={(value) => formatCurrency(value)}
                                        />
                                    </Col>
                                    <Col span={6}>
                                        <Statistic
                                            title="Thu"
                                            value={reportData.cash_drawer?.cash_in || 0}
                                            formatter={(value) => formatCurrency(value)}
                                            styles={{ content: { color: '#3f8600' } }}
                                        />
                                    </Col>
                                    <Col span={6}>
                                        <Statistic
                                            title="Chi"
                                            value={reportData.cash_drawer?.cash_out || 0}
                                            formatter={(value) => formatCurrency(value)}
                                            styles={{ content: { color: '#cf1322' } }}
                                        />
                                    </Col>
                                    <Col span={6}>
                                        <Statistic
                                            title="Tồn quỹ"
                                            value={reportData.cash_drawer?.expected_balance || 0}
                                            formatter={(value) => formatCurrency(value)}
                                            styles={{ content: { color: '#1890ff' } }}
                                        />
                                    </Col>
                                </Row>
                            </Card>
                        </Col>
                    </Row>

                    {/* Payment Breakdown & Top Products */}
                    <Row gutter={[16, 16]} className={styles.section}>
                        <Col xs={24} md={10}>
                            <Card title="Phương thức thanh toán" className={styles.tableCard}>
                                <Table
                                    columns={paymentColumns}
                                    dataSource={(reportData.payments || []).map((p, i) => ({
                                        key: i,
                                        method: p.method || p.payment_method || 'Khác',
                                        count: p.count || 0,
                                        amount: p.amount || p.total || 0,
                                    }))}
                                    pagination={false}
                                    size="small"
                                    locale={{ emptyText: <Empty description="Chưa có dữ liệu" /> }}
                                />
                            </Card>
                        </Col>
                        <Col xs={24} md={14}>
                            <Card title="Top 10 sản phẩm bán chạy" className={styles.tableCard}>
                                <Table
                                    columns={topProductsColumns}
                                    dataSource={(reportData.top_products || []).map((p, i) => ({
                                        key: i,
                                        name: p.name || p.product_name,
                                        code: p.code || p.product_code,
                                        quantity: p.quantity || p.total_quantity || 0,
                                        revenue: p.revenue || p.total_revenue || 0,
                                    }))}
                                    pagination={false}
                                    size="small"
                                    locale={{ emptyText: <Empty description="Chưa có dữ liệu" /> }}
                                />
                            </Card>
                        </Col>
                    </Row>
                </>
            ) : (
                <div className={styles.empty}>
                    <Empty description="Không có dữ liệu báo cáo" />
                </div>
            )}
        </div>
    );
};

export default DailyReportPage;
