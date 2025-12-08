import React, { useState, useEffect, useCallback } from 'react';
import { Modal, Input, DatePicker, Table, Button, Pagination, Spin } from 'antd';
import posApi from '../../api/posApi';
import styles from './ReturnInvoiceModal.module.css';

const ReturnInvoiceModal = ({ open, onClose, onSelect, onQuickReturn }) => {
    const [searchType, setSearchType] = useState('code');
    const [searchValue, setSearchValue] = useState('');
    const [fromDate, setFromDate] = useState(null);
    const [toDate, setToDate] = useState(null);
    const [currentPage, setCurrentPage] = useState(1);
    const [invoices, setInvoices] = useState([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(false);
    const pageSize = 7;

    const fetchInvoices = useCallback(async () => {
        if (!open) return;
        setLoading(true);
        try {
            const response = await posApi.getInvoices({
                search: searchValue || undefined,
                searchType: searchType || undefined,
                fromDate: fromDate ? fromDate.format('YYYY-MM-DD') : undefined,
                toDate: toDate ? toDate.format('YYYY-MM-DD') : undefined,
                page: currentPage,
                limit: pageSize,
            });
            if (response.success) {
                // Map orders data to invoice display format
                setInvoices(response.data.map(order => ({
                    id: order.id,
                    code: order.order_number,
                    time: order.order_date || order.created_at,
                    staff: order.user_name || order.created_by_name || 'N/A',
                    customer: order.customer_name || 'Khách lẻ',
                    total: parseFloat(order.total) || 0,
                })));
                setTotal(response.pagination?.total || 0);
            }
        } catch (error) {
            console.error('Failed to fetch invoices:', error);
        } finally {
            setLoading(false);
        }
    }, [open, searchValue, searchType, fromDate, toDate, currentPage]);

    useEffect(() => {
        fetchInvoices();
    }, [fetchInvoices]);

    const searchOptions = [
        { key: 'code', label: 'Theo mã hóa đơn' },
        { key: 'shipping', label: 'Theo mã vận đơn bán' },
        { key: 'customer', label: 'Theo khách hàng hoặc ĐT' },
        { key: 'productCode', label: 'Theo mã hàng' },
        { key: 'productName', label: 'Theo tên hàng' },
    ];

    const columns = [
        {
            title: 'Mã hóa đơn',
            dataIndex: 'code',
            key: 'code',
            render: (code) => <a className={styles.linkCode}>{code}</a>,
        },
        {
            title: 'Thời gian',
            dataIndex: 'time',
            key: 'time',
            sorter: true,
        },
        {
            title: 'Nhân viên',
            dataIndex: 'staff',
            key: 'staff',
        },
        {
            title: 'Khách hàng',
            dataIndex: 'customer',
            key: 'customer',
        },
        {
            title: 'Tổng cộng',
            dataIndex: 'total',
            key: 'total',
            align: 'right',
            render: (val) => val.toLocaleString('vi-VN'),
        },
        {
            title: '',
            key: 'action',
            render: (_, record) => (
                <Button onClick={() => onSelect?.(record)}>Chọn</Button>
            ),
        },
    ];

    return (
        <Modal
            title="Chọn hóa đơn trả hàng"
            open={open}
            onCancel={onClose}
            footer={null}
            width={950}
            className={styles.modal}
        >
            <div className={styles.content}>
                {/* Left Sidebar */}
                <div className={styles.sidebar}>
                    <div className={styles.searchSection}>
                        <h4>Tìm kiếm</h4>
                        <Input
                            placeholder="Theo mã hóa đơn"
                            className={styles.searchInput}
                            value={searchValue}
                            onChange={(e) => setSearchValue(e.target.value)}
                        />
                        {searchOptions.map((opt) => (
                            <div
                                key={opt.key}
                                className={`${styles.searchOption} ${searchType === opt.key ? styles.active : ''}`}
                                onClick={() => setSearchType(opt.key)}
                            >
                                {opt.label}
                            </div>
                        ))}
                    </div>

                    <div className={styles.dateSection}>
                        <h4>Thời gian</h4>
                        <DatePicker
                            placeholder="Từ ngày"
                            value={fromDate}
                            onChange={setFromDate}
                            className={styles.datePicker}
                        />
                        <DatePicker
                            placeholder="Đến ngày"
                            value={toDate}
                            onChange={setToDate}
                            className={styles.datePicker}
                        />
                    </div>
                </div>

                {/* Main Content */}
                <div className={styles.mainContent}>
                    <Spin spinning={loading}>
                        <Table
                            columns={columns}
                            dataSource={invoices}
                            rowKey="id"
                            pagination={false}
                            size="small"
                            locale={{ emptyText: 'Không có hóa đơn nào' }}
                        />
                    </Spin>

                    <div className={styles.footer}>
                        <Pagination
                            current={currentPage}
                            total={total}
                            pageSize={pageSize}
                            onChange={setCurrentPage}
                            showSizeChanger={false}
                            size="small"
                        />
                        <span className={styles.totalText}>
                            Hiển thị {Math.min((currentPage - 1) * pageSize + 1, total)} - {Math.min(currentPage * pageSize, total)} trên tổng số {total} hóa đơn
                        </span>
                        <Button type="primary" onClick={onQuickReturn} className={styles.quickReturnBtn}>
                            Trả nhanh
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>
    );
};

export default ReturnInvoiceModal;
