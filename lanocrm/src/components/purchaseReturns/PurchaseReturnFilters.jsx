// src/components/purchaseReturns/PurchaseReturnFilters.jsx
import React from 'react';
import { Card, Checkbox, DatePicker, Radio, Select, Space, Tag } from 'antd';
import { useSelector } from 'react-redux';
import dayjs from 'dayjs';
import { PURCHASE_RETURN_STATUSES } from '../../constants/purchaseReturns';
import styles from '../../pages/suppliers/SupplierListPage.module.css';

const { RangePicker } = DatePicker;

const EMPTY_ARRAY = [];

/**
 * PurchaseReturnFilters - Filter sidebar for purchase returns (Trả hàng nhập)
 * @agent-layer: frontend-component
 * @agent-pattern: filter-sidebar
 */
const PurchaseReturnFilters = ({ filters, onChange }) => {
    const branchItems = useSelector((state) => state.branch?.items ?? EMPTY_ARRAY);
    const users = useSelector((state) => state.user?.items ?? EMPTY_ARRAY);

    const handleStatusChange = (status, checked) => {
        const current = filters.status || [];
        const newStatus = checked ? [...current, status] : current.filter((s) => s !== status);
        onChange({ status: newStatus.length > 0 ? newStatus : null });
    };

    const handleTimeChange = (mode) => {
        if (mode === 'month') {
            const start = dayjs().startOf('month').format('YYYY-MM-DD');
            const end = dayjs().format('YYYY-MM-DD');
            onChange({ date_from: start, date_to: end, time_mode: 'month' });
        } else if (mode === 'year') {
            const start = dayjs().subtract(1, 'year').format('YYYY-MM-DD');
            const end = dayjs().format('YYYY-MM-DD');
            onChange({ date_from: start, date_to: end, time_mode: 'year' });
        } else if (mode === 'all') {
            onChange({ date_from: null, date_to: null, time_mode: 'all' });
        }
    };

    const handleDateRange = (dates) => {
        if (dates && dates[0] && dates[1]) {
            onChange({
                date_from: dates[0].format('YYYY-MM-DD'),
                date_to: dates[1].format('YYYY-MM-DD'),
                time_mode: 'custom',
            });
        } else {
            onChange({ date_from: null, date_to: null, time_mode: 'all' });
        }
    };

    const handleBranchRemove = () => {
        onChange({ branch_id: null });
    };

    return (
        <Card title="Trả hàng nhập" className={styles.filterCard} size="small" bordered>
            {/* Branch filter */}
            <div className={styles.filterGroup}>
                <div className={styles.filterLabel}>Chi nhánh</div>
                {filters.branch_id ? (
                    <Tag
                        closable
                        onClose={handleBranchRemove}
                        color="blue"
                        style={{ marginBottom: 8 }}
                    >
                        {branchItems.find(b => b.id === filters.branch_id)?.name || 'Chi nhánh'}
                    </Tag>
                ) : null}
                <Select
                    style={{ width: '100%' }}
                    placeholder="Chọn chi nhánh"
                    allowClear
                    value={filters.branch_id}
                    onChange={(value) => onChange({ branch_id: value })}
                    options={branchItems.map((b) => ({ label: b.name, value: b.id }))}
                />
            </div>

            {/* Status filter */}
            <div className={styles.filterGroup}>
                <div className={styles.filterLabel}>Trạng thái</div>
                <Space direction="vertical" size={4}>
                    {PURCHASE_RETURN_STATUSES.map((s) => (
                        <Checkbox
                            key={s.value}
                            checked={(filters.status || []).includes(s.value)}
                            onChange={(e) => handleStatusChange(s.value, e.target.checked)}
                        >
                            {s.label}
                        </Checkbox>
                    ))}
                </Space>
            </div>

            {/* Time filter */}
            <div className={styles.filterGroup}>
                <div className={styles.filterLabel}>Thời gian *</div>
                <Radio.Group
                    value={filters.time_mode || 'month'}
                    onChange={(e) => handleTimeChange(e.target.value)}
                >
                    <Space direction="vertical">
                        <Radio value="month">Tháng này</Radio>
                        <Radio value="year">Năm trước</Radio>
                        <Radio value="custom">
                            <Space>
                                Tùy chỉnh
                                <RangePicker
                                    size="small"
                                    disabled={(filters.time_mode || 'month') !== 'custom'}
                                    value={
                                        filters.date_from && filters.date_to
                                            ? [dayjs(filters.date_from), dayjs(filters.date_to)]
                                            : null
                                    }
                                    onChange={handleDateRange}
                                />
                            </Space>
                        </Radio>
                    </Space>
                </Radio.Group>
            </div>

            {/* Creator filter */}
            <div className={styles.filterGroup}>
                <div className={styles.filterLabel}>Người tạo</div>
                <Select
                    style={{ width: '100%' }}
                    placeholder="Chọn người tạo"
                    allowClear
                    showSearch
                    optionFilterProp="label"
                    value={filters.created_by}
                    onChange={(value) => onChange({ created_by: value })}
                    options={users.map((u) => ({ label: u.name || u.full_name, value: u.id }))}
                />
            </div>

            {/* Returner filter */}
            <div className={styles.filterGroup}>
                <div className={styles.filterLabel}>Người trả</div>
                <Select
                    style={{ width: '100%' }}
                    placeholder="Chọn người trả"
                    allowClear
                    showSearch
                    optionFilterProp="label"
                    value={filters.returned_by}
                    onChange={(value) => onChange({ returned_by: value })}
                    options={users.map((u) => ({ label: u.name || u.full_name, value: u.id }))}
                />
            </div>
        </Card>
    );
};

export default PurchaseReturnFilters;
