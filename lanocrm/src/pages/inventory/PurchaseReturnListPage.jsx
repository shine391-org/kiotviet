// src/pages/inventory/PurchaseReturnListPage.jsx
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

import styles from '../suppliers/SupplierListPage.module.css';
import PurchaseReturnFilters from '../../components/purchaseReturns/PurchaseReturnFilters';
import PurchaseReturnDetailPanel from '../../components/purchaseReturns/PurchaseReturnDetailPanel';
import {
    fetchPurchaseReturns,
    fetchPurchaseReturnDetail,
    setPurchaseReturnFilters,
} from '../../store/slices/purchaseReturnSlice';
import {
    PURCHASE_RETURN_STATUSES,
    ALL_PURCHASE_RETURN_COLUMNS,
    DEFAULT_PURCHASE_RETURN_COLUMNS,
    formatPurchaseReturnDate,
} from '../../constants/purchaseReturns';
import purchaseReturnApi from '../../api/purchaseReturnApi';

const currency = (value) => {
    if (value === null || value === undefined) return '0';
    return new Intl.NumberFormat('vi-VN').format(value);
};

/**
 * PurchaseReturnListPage - Purchase returns list page (Trả hàng nhập)
 * @agent-layer: frontend-page
 * @agent-pattern: list-with-sticky-filter + detail tabs (like PurchaseListPage)
 */
const PurchaseReturnListPage = () => {
    const { message } = App.useApp();
    const dispatch = useDispatch();
    const navigate = useNavigate();

    const { items, pagination, filters, loading, current, detailLoading, pageTotals } =
        useSelector((state) => state.purchaseReturns);

    const [searchText, setSearchText] = useState('');
    const [selectedId, setSelectedId] = useState(null);
    const [visibleCols, setVisibleCols] = useState(DEFAULT_PURCHASE_RETURN_COLUMNS);
    const [exporting, setExporting] = useState(false);

    // Fetch data on mount and filter change
    useEffect(() => {
        dispatch(fetchPurchaseReturns());
    }, [dispatch, filters]);

    // Debounce search
    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchText !== (filters.search || '')) {
                dispatch(setPurchaseReturnFilters({ search: searchText }));
            }
        }, 350);
        return () => clearTimeout(timer);
    }, [searchText, dispatch, filters.search]);

    // Fetch detail when selection changes
    useEffect(() => {
        if (selectedId) {
            dispatch(fetchPurchaseReturnDetail(selectedId));
        }
    }, [selectedId, dispatch]);

    const handleFilterChange = (newFilters) => {
        dispatch(setPurchaseReturnFilters(newFilters));
    };

    const toggleColumn = (key) => {
        setVisibleCols((prev) =>
            prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]
        );
    };

    const handleExport = async () => {
        setExporting(true);
        try {
            const response = await purchaseReturnApi.exportPurchaseReturns(filters);
            const blob = new Blob([response.data], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `purchase_returns_${new Date().toISOString().slice(0, 10)}.xlsx`;
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
                key: 'return_number',
                title: 'Mã trả hàng nhập',
                dataIndex: 'return_number',
                width: 150,
                render: (text) => (
                    <a onClick={(e) => e.stopPropagation()} style={{ color: '#1890ff' }}>
                        {text || '—'}
                    </a>
                ),
            },
            {
                key: 'purchase_order_number',
                title: 'Mã nhập hàng',
                dataIndex: 'purchase_order_number',
                width: 140,
                render: (v) => v || '—',
            },
            {
                key: 'return_date',
                title: 'Thời gian',
                dataIndex: 'return_date',
                width: 140,
                render: (v) => formatPurchaseReturnDate(v),
            },
            {
                key: 'created_at',
                title: 'Thời gian tạo',
                dataIndex: 'created_at',
                width: 140,
                render: (v) => formatPurchaseReturnDate(v),
            },
            {
                key: 'supplier_name',
                title: 'Nhà cung cấp',
                dataIndex: 'supplier_name',
                width: 200,
                ellipsis: true,
            },
            {
                key: 'branch_name',
                title: 'Chi nhánh',
                dataIndex: 'branch_name',
                width: 140,
            },
            {
                key: 'returner_name',
                title: 'Người trả',
                dataIndex: 'returner_name',
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
                title: 'Số lượng mặt hàng',
                dataIndex: 'total_quantity',
                width: 140,
                align: 'right',
                render: (v) => v || 0,
            },
            {
                key: 'total_amount',
                title: 'Tổng tiền hàng',
                dataIndex: 'total_amount',
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
                render: (v) => <span style={{ color: '#52c41a' }}>{currency(v || 0)}</span>,
            },
            {
                key: 'ncc_can_tra',
                title: 'NCC cần trả',
                dataIndex: 'ncc_can_tra',
                width: 130,
                align: 'right',
                render: (v) => currency(v),
            },
            {
                key: 'ncc_da_tra',
                title: 'NCC đã trả',
                dataIndex: 'ncc_da_tra',
                width: 130,
                align: 'right',
                render: (v) => <span style={{ color: '#52c41a' }}>{currency(v)}</span>,
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
                    const info = PURCHASE_RETURN_STATUSES.find((s) => s.value === v) || {};
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
        items: ALL_PURCHASE_RETURN_COLUMNS.map((col) => ({
            key: col.key,
            label: (
                <Checkbox
                    checked={visibleCols.includes(col.key)}
                    onChange={() => toggleColumn(col.key)}
                >
                    {col.label}
                </Checkbox>
            ),
        })),
    };

    return (
        <div className={styles.page}>
            <div className={styles.content}>
                {/* Filter sidebar */}
                <PurchaseReturnFilters filters={filters} onChange={handleFilterChange} />

                {/* Main content */}
                <div className={styles.contentMain}>
                    <div className={styles.tablePanel}>
                        {/* Toolbar */}
                        <div className={styles.toolbarRow}>
                            <Input
                                className={styles.searchBox}
                                placeholder="Theo mã phiếu trả"
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
                                    onClick={() => navigate('/inventory/purchase-returns/new')}
                                >
                                    Trả hàng nhập
                                </Button>
                                <Dropdown
                                    menu={{
                                        items: [
                                            { key: 'excel', label: 'Xuất Excel', onClick: handleExport },
                                        ],
                                    }}
                                    trigger={['click']}
                                >
                                    <Button icon={<ExportOutlined />} loading={exporting}>
                                        Xuất file
                                    </Button>
                                </Dropdown>
                                <Dropdown menu={columnMenu} trigger={['click']} placement="bottomRight">
                                    <Button icon={<MenuOutlined />} />
                                </Dropdown>
                                <Tooltip title="Làm mới">
                                    <Button
                                        icon={<ReloadOutlined />}
                                        onClick={() => dispatch(fetchPurchaseReturns())}
                                    />
                                </Tooltip>
                            </Space>
                        </div>

                        {/* Summary row */}
                        <div className={styles.summaryRow}>
                            <div className={styles.summaryItem}>
                                <span className={styles.summaryLabel}>Tổng tiền hàng</span>
                                <span className={styles.summaryValue}>{currency(pageTotals?.total_amount || 0)}</span>
                            </div>
                            <div className={styles.summaryItem}>
                                <span className={styles.summaryLabel}>Giảm giá</span>
                                <span className={styles.summaryValue} style={{ color: '#52c41a' }}>
                                    {currency(pageTotals?.total_discount || 0)}
                                </span>
                            </div>
                            <div className={styles.summaryItem}>
                                <span className={styles.summaryLabel}>NCC cần trả</span>
                                <span className={styles.summaryValue}>
                                    {currency(pageTotals?.total_ncc_can_tra || 0)}
                                </span>
                            </div>
                            <div className={styles.summaryItem}>
                                <span className={styles.summaryLabel}>NCC đã trả</span>
                                <span className={styles.summaryValue} style={{ color: '#52c41a' }}>
                                    {currency(pageTotals?.total_ncc_da_tra || 0)}
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
                                        `Hiển thị ${range[0]}-${range[1]} trong ${total} phiếu`,
                                    onChange: (page, pageSize) =>
                                        dispatch(setPurchaseReturnFilters({ page, limit: pageSize })),
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
                                        <PurchaseReturnDetailPanel
                                            purchaseReturn={current?.id === record.id ? current : record}
                                            loading={detailLoading}
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
            </div>
        </div>
    );
};

export default PurchaseReturnListPage;
