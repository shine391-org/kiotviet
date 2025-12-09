// src/pages/inventory/PurchaseListPage.jsx
import React, { useEffect, useState, useMemo } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import {
    App,
    Button,
    Checkbox,
    Dropdown,
    Input,
    Space,
    Table,
    Tag,
    Tooltip,
} from 'antd';
import {
    ExportOutlined,
    FilterOutlined,
    MenuOutlined,
    PlusOutlined,
    ReloadOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';

import styles from '../suppliers/SupplierListPage.module.css';
import PurchaseFilters from '../../components/purchases/PurchaseFilters';
import PurchaseDetailPanel from '../../components/purchases/PurchaseDetailPanel';
import EditPaymentReceiptModal from '../suppliers/ReceiptDetailModal';
import {
    fetchPurchases,
    fetchPurchaseDetail,
    setPurchaseFilters,
} from '../../store/slices/purchaseSlice';
import {
    PURCHASE_STATUSES,
    ALL_PURCHASE_COLUMNS,
    DEFAULT_PURCHASE_COLUMNS,
    formatPurchaseDate,
} from '../../constants/purchases';
import purchaseApi from '../../api/purchaseApi';

const currency = (value) => {
    if (value === null || value === undefined) return '0';
    return new Intl.NumberFormat('vi-VN').format(value);
};

/**
 * PurchaseListPage - Purchase orders list page
 * @agent-layer: frontend-page
 * @agent-pattern: list-with-sticky-filter + detail tabs (like SupplierListPage)
 */
const PurchaseListPage = () => {
    const { message } = App.useApp();
    const dispatch = useDispatch();
    const navigate = useNavigate();

    const { items, pagination, filters, loading, current, detailLoading, totals, pageTotals } =
        useSelector((state) => state.purchases);

    const [searchText, setSearchText] = useState('');
    const [selectedId, setSelectedId] = useState(null);
    const [visibleCols, setVisibleCols] = useState(DEFAULT_PURCHASE_COLUMNS);
    const [receiptModalOpen, setReceiptModalOpen] = useState(false);
    const [selectedReceiptCode, setSelectedReceiptCode] = useState(null);
    const [exporting, setExporting] = useState(false);

    // Fetch data on mount and filter change
    useEffect(() => {
        dispatch(fetchPurchases());
    }, [dispatch, filters]);

    // Debounce search
    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchText !== (filters.search || '')) {
                dispatch(setPurchaseFilters({ search: searchText }));
            }
        }, 350);
        return () => clearTimeout(timer);
    }, [searchText, dispatch, filters.search]);

    // Fetch detail when selection changes
    useEffect(() => {
        if (selectedId) {
            dispatch(fetchPurchaseDetail(selectedId));
        }
    }, [selectedId, dispatch]);

    const handleFilterChange = (newFilters) => {
        dispatch(setPurchaseFilters(newFilters));
    };

    const handleReceiptCodeClick = (code) => {
        setSelectedReceiptCode(code);
        setReceiptModalOpen(true);
    };

    const toggleColumn = (key) => {
        setVisibleCols((prev) =>
            prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]
        );
    };

    const handleExport = async () => {
        setExporting(true);
        try {
            const response = await purchaseApi.exportPurchases(filters);
            const blob = new Blob([response.data], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `purchase_orders_${new Date().toISOString().slice(0, 10)}.xlsx`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
            message.success('Xuất file thành công');
        } catch (error) {
            console.error('Export error:', error);
            message.error('Lỗi xuất file: ' + (error.message || 'Không xác định'));
        } finally {
            setExporting(false);
        }
    };

    const allColumns = useMemo(
        () => [
            {
                key: 'order_number',
                title: 'Mã nhập hàng',
                dataIndex: 'order_number',
                width: 140,
                render: (text, record) => (
                    <a onClick={(e) => { e.stopPropagation(); }} style={{ color: '#1890ff' }}>
                        {text || record.po_number || '—'}
                    </a>
                ),
            },
            {
                key: 'return_code',
                title: 'Mã trả hàng nhập',
                dataIndex: 'return_code',
                width: 140,
                render: (v) => v || '—',
            },
            {
                key: 'order_date',
                title: 'Thời gian',
                dataIndex: 'order_date',
                width: 140,
                render: (v) => formatPurchaseDate(v),
            },
            {
                key: 'created_at',
                title: 'Thời gian tạo',
                dataIndex: 'created_at',
                width: 140,
                render: (v) => formatPurchaseDate(v),
            },
            {
                key: 'updated_at',
                title: 'Ngày cập nhật',
                dataIndex: 'updated_at',
                width: 140,
                render: (v) => formatPurchaseDate(v),
            },
            {
                key: 'supplier_code',
                title: 'Mã NCC',
                dataIndex: 'supplier_code',
                width: 100,
            },
            {
                key: 'supplier_name',
                title: 'Nhà cung cấp',
                dataIndex: 'supplier_name',
                width: 180,
                ellipsis: true,
            },
            {
                key: 'branch_name',
                title: 'Chi nhánh',
                dataIndex: 'branch_name',
                width: 140,
            },
            {
                key: 'receiver_name',
                title: 'Người nhập',
                dataIndex: 'receiver_name',
                width: 120,
            },
            {
                key: 'creator_name',
                title: 'Người tạo',
                dataIndex: 'creator_name',
                width: 120,
            },
            {
                key: 'total_quantity',
                title: 'Tổng số lượng',
                dataIndex: 'total_quantity',
                width: 110,
                align: 'right',
                render: (v) => v || 0,
            },
            {
                key: 'total',
                title: 'Tổng tiền hàng',
                dataIndex: 'total',
                width: 130,
                align: 'right',
                render: (v) => currency(v),
            },
            {
                key: 'discount',
                title: 'Giảm giá',
                dataIndex: 'discount',
                width: 100,
                align: 'right',
                render: (v) => currency(v || 0),
            },
            {
                key: 'paid_amount',
                title: 'Tiền đã trả NCC',
                dataIndex: 'paid_amount',
                width: 130,
                align: 'right',
                render: (v) => currency(v),
            },
            {
                key: 'debt',
                title: 'Cần trả NCC',
                dataIndex: 'debt',
                width: 130,
                align: 'right',
                render: (v) => (
                    <span style={{ color: v > 0 ? '#f5222d' : '#52c41a' }}>{currency(v)}</span>
                ),
            },
            {
                key: 'notes',
                title: 'Ghi chú',
                dataIndex: 'notes',
                width: 200,
                ellipsis: true,
            },
            {
                key: 'status',
                title: 'Trạng thái',
                dataIndex: 'status',
                width: 130,
                render: (v) => {
                    const info = PURCHASE_STATUSES.find((s) => s.value === v) || {};
                    return <Tag color={info.color}>{info.label || v}</Tag>;
                },
            },
        ],
        []
    );

    const columns = useMemo(
        () => allColumns.filter((col) => visibleCols.includes(col.key)),
        [allColumns, visibleCols]
    );

    const columnMenu = {
        items: allColumns.map((col) => ({
            key: col.key,
            label: (
                <Checkbox
                    checked={visibleCols.includes(col.key)}
                    onChange={() => toggleColumn(col.key)}
                >
                    {col.title}
                </Checkbox>
            ),
        })),
    };

    return (
        <div className={styles.page}>
            <div className={styles.content}>
                {/* Filter sidebar */}
                <PurchaseFilters filters={filters} onChange={handleFilterChange} />

                {/* Main content */}
                <div className={styles.contentMain}>
                    <div className={styles.tablePanel}>
                        {/* Toolbar */}
                        <div className={styles.toolbarRow}>
                            <Input
                                className={styles.searchBox}
                                placeholder="Theo mã nhập hàng, mã NCC"
                                prefix={<FilterOutlined />}
                                value={searchText}
                                onChange={(e) => setSearchText(e.target.value)}
                                allowClear
                                size="middle"
                            />
                            <Space className={styles.actions} wrap>
                                <Button
                                    type="primary"
                                    icon={<PlusOutlined />}
                                    onClick={() => navigate('/inventory/purchase/new')}
                                >
                                    Nhập hàng
                                </Button>
                                <Button icon={<ExportOutlined />} onClick={handleExport} loading={exporting}>Xuất file</Button>
                                <Dropdown menu={columnMenu} trigger={['click']} placement="bottomRight">
                                    <Button icon={<MenuOutlined />} />
                                </Dropdown>
                                <Tooltip title="Làm mới">
                                    <Button
                                        icon={<ReloadOutlined />}
                                        onClick={() => dispatch(fetchPurchases())}
                                    />
                                </Tooltip>
                            </Space>
                        </div>

                        {/* Summary row */}
                        <div className={styles.summaryRow}>
                            <div className={styles.summaryItem}>
                                <span className={styles.summaryLabel}>Tổng tiền hàng</span>
                                <span className={styles.summaryValue}>{currency(pageTotals.total_amount)}</span>
                            </div>
                            <div className={styles.summaryItem}>
                                <span className={styles.summaryLabel}>Đã trả NCC</span>
                                <span className={styles.summaryValue}>{currency(pageTotals.total_paid)}</span>
                            </div>
                            <div className={styles.summaryItem}>
                                <span className={styles.summaryLabel}>Cần trả NCC</span>
                                <span className={styles.summaryValue} style={{ color: '#f5222d' }}>
                                    {currency(pageTotals.total_debt)}
                                </span>
                            </div>
                        </div>

                        {/* Table */}
                        <div className={styles.tableWrapper}>
                            <Table
                                rowKey="id"
                                loading={loading}
                                bordered
                                dataSource={items}
                                columns={columns}
                                pagination={{
                                    current: pagination.page,
                                    pageSize: pagination.limit,
                                    total: pagination.total,
                                    showSizeChanger: true,
                                    showTotal: (total, range) =>
                                        `${range[0]}-${range[1]} trong ${total} phiếu nhập`,
                                    onChange: (page, pageSize) =>
                                        dispatch(setPurchaseFilters({ page, limit: pageSize })),
                                }}
                                rowSelection={{
                                    type: 'checkbox',
                                    selectedRowKeys: selectedId ? [selectedId] : [],
                                    onChange: (keys) => {
                                        const id = keys[keys.length - 1];
                                        if (id && id !== selectedId) {
                                            setSelectedId(id);
                                        }
                                    },
                                }}
                                expandable={{
                                    expandedRowKeys: selectedId ? [selectedId] : [],
                                    expandIcon: () => null,
                                    expandedRowRender: (record) => (
                                        <PurchaseDetailPanel
                                            purchase={current?.id === record.id ? current : record}
                                            loading={detailLoading}
                                            onReceiptCodeClick={handleReceiptCodeClick}
                                        />
                                    ),
                                }}
                                onRow={(record) => ({
                                    onClick: () => {
                                        if (record.id === selectedId) {
                                            setSelectedId(null);
                                        } else {
                                            setSelectedId(record.id);
                                        }
                                    },
                                    className: record.id === selectedId ? styles.selectedRow : '',
                                })}
                                size="middle"
                                scroll={{ x: 1200 }}
                            />
                        </div>
                    </div>
                </div>

                {/* Receipt Detail Modal */}
                <EditPaymentReceiptModal
                    open={receiptModalOpen}
                    onCancel={() => setReceiptModalOpen(false)}
                    receiptCode={selectedReceiptCode}
                />
            </div>
        </div>
    );
};

export default PurchaseListPage;
