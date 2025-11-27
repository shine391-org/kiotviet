import React, { useEffect, useMemo, useState } from 'react';
import { Card, Radio, Select, DatePicker, Checkbox, Button, Input, Space, Tooltip, Divider } from 'antd';
import dayjs from 'dayjs';
import {
  CASH_TYPES,
  PAYMENT_METHODS,
  STATUS_OPTIONS,
  getCategoriesByType,
  REFERENCE_TYPES,
} from '../../constants/cash';
import styles from '../../pages/cash/CashBookPage.module.css';

const { RangePicker } = DatePicker;

const CashFilters = ({
  filters,
  onChange,
  branches,
  loading = false,
}) => {
  const [rangeMode, setRangeMode] = useState('this_month');
  const [typeValue, setTypeValue] = useState(
    filters.type ? [filters.type] : ['RECEIPT', 'PAYMENT']
  );

  useEffect(() => {
    setTypeValue(filters.type ? [filters.type] : ['RECEIPT', 'PAYMENT']);
  }, [filters.type]);

  const handleDateMode = (mode) => {
    setRangeMode(mode);
    if (mode === 'this_month') {
      const start = dayjs().startOf('month');
      const end = dayjs();
      onChange({
        date_from: start.format('YYYY-MM-DD'),
        date_to: end.format('YYYY-MM-DD'),
      });
    }
  };

  const handleCustomRange = (range) => {
    if (!range || range.length !== 2) return;
    const [start, end] = range;
    onChange({
      date_from: start.format('YYYY-MM-DD'),
      date_to: end.format('YYYY-MM-DD'),
    });
  };

  const handleTypeChange = (checkedValues) => {
    setTypeValue(checkedValues);
    if (checkedValues.length === 1) {
      onChange({ type: checkedValues[0] });
    } else {
      onChange({ type: null });
    }
  };

  const categoryOptions = useMemo(() => {
    if (typeValue.length === 1) {
      return getCategoriesByType(typeValue[0]);
    }
    return getCategoriesByType(null);
  }, [typeValue]);

  const handleReset = () => {
    setRangeMode('this_month');
    setTypeValue(['RECEIPT', 'PAYMENT']);
    onChange({
      search: '',
      type: null,
      category: null,
      branch_id: null,
      payment_method: null,
      status: null,
      reference_type: null,
      payer_name: null,
      payer_phone: null,
      date_from: dayjs().startOf('month').format('YYYY-MM-DD'),
      date_to: dayjs().format('YYYY-MM-DD'),
    });
  };

  return (
    <Card bordered={false} className={styles.filterCard} bodyStyle={{ padding: 0 }}>
      <div style={{ padding: '8px 12px 0' }}>
        <div className={styles.filterTitle}>Sổ quỹ tiền mặt</div>

        <div className={styles.filterGroup}>
          <div className={styles.filterLabel}>Quỹ tiền</div>
          <Radio.Group
            onChange={(e) => onChange({ payment_method: e.target.value || null })}
            value={filters.payment_method || ''}
            buttonStyle="solid"
          >
            <Radio.Button value="">Tất cả</Radio.Button>
            {PAYMENT_METHODS.map((m) => (
              <Radio.Button key={m.value} value={m.value}>{m.label}</Radio.Button>
            ))}
          </Radio.Group>
        </div>

        <div className={styles.filterGroup}>
          <div className={styles.filterLabel}>Chi nhánh</div>
          <Select
            style={{ width: '100%' }}
            placeholder="Chọn chi nhánh"
            allowClear
            showSearch
            optionFilterProp="label"
            value={filters.branch_id || undefined}
            onChange={(value) => onChange({ branch_id: value || null })}
            options={branches.map((b) => ({
              value: b.id,
              label: b.name || b.code || `CN #${b.id}`,
            }))}
          />
        </div>

        <div className={styles.filterGroup}>
          <div className={styles.filterLabel}>Thời gian</div>
          <Radio.Group
            onChange={(e) => handleDateMode(e.target.value)}
            value={rangeMode}
            style={{ marginBottom: 6 }}
          >
            <Radio value="this_month">Tháng này</Radio>
            <Radio value="custom">Tùy chỉnh</Radio>
          </Radio.Group>
          <RangePicker
            allowClear={false}
            style={{ width: '100%' }}
            disabled={rangeMode !== 'custom'}
            value={[
              filters.date_from ? dayjs(filters.date_from) : null,
              filters.date_to ? dayjs(filters.date_to) : null,
            ]}
            format="DD/MM/YYYY"
            onChange={handleCustomRange}
          />
        </div>

        <div className={styles.filterGroup}>
          <div className={styles.filterLabel}>Loại chứng từ</div>
          <Checkbox.Group
            options={CASH_TYPES.map((t) => ({ label: t.label, value: t.value }))}
            value={typeValue}
            onChange={handleTypeChange}
          />
        </div>

        <div className={styles.filterGroup}>
          <div className={styles.filterLabel}>{typeValue.length === 1 && typeValue[0] === 'PAYMENT' ? 'Loại chi' : 'Loại thu/chi'}</div>
          <Select
            allowClear
            placeholder="Chọn loại"
            style={{ width: '100%' }}
            value={filters.category || undefined}
            options={categoryOptions}
            onChange={(value) => onChange({ category: value || null })}
          />
        </div>

        <div className={styles.filterGroup}>
          <div className={styles.filterLabel}>Trạng thái</div>
          <Checkbox.Group
            options={STATUS_OPTIONS.map((s) => ({ label: s.label, value: s.value }))}
            value={filters.status ? [filters.status] : []}
            onChange={(values) => onChange({ status: values[0] || null })}
          />
        </div>

        <div className={styles.filterGroup}>
          <div className={styles.filterLabel}>Loại tham chiếu</div>
          <Select
            allowClear
            style={{ width: '100%' }}
            placeholder="Đơn hàng / PO / Manual"
            value={filters.reference_type || undefined}
            options={REFERENCE_TYPES}
            onChange={(value) => onChange({ reference_type: value || null })}
          />
        </div>

        <div className={styles.filterGroup}>
          <div className={styles.filterLabel}>Người nộp/nhận</div>
          <Input
            placeholder="Tên người nộp/nhận"
            value={filters.payer_name || ''}
            onChange={(e) => onChange({ payer_name: e.target.value })}
          />
          <Input
            style={{ marginTop: 6 }}
            placeholder="Số điện thoại"
            value={filters.payer_phone || ''}
            onChange={(e) => onChange({ payer_phone: e.target.value })}
          />
        </div>

        <Divider style={{ margin: '10px 0' }} />

        <Space style={{ width: '100%', justifyContent: 'space-between' }}>
          <Tooltip title="Đặt lại toàn bộ bộ lọc">
            <Button onClick={handleReset} disabled={loading}>
              Đặt lại
            </Button>
          </Tooltip>
          <span className={styles.warningText}>Bộ lọc đã áp dụng</span>
        </Space>
      </div>
    </Card>
  );
};

export default CashFilters;
